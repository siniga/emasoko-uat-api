<?php

namespace App\Http\Controllers;

use App\Classes\Messaging;
use App\Exports\CollectionCreatedExport;
use App\Exports\CollectionProcessedExport;
use App\Exports\CollectionPayments;
use App\Http\Requests\CollectionRequest;
use App\Jobs\CollectionStatusJob;
use App\Jobs\PushCollectionJob;
use App\Jobs\SendSMSJob;
use App\Models\Account;
use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\Partner;
use App\Models\User;
use App\Services\BankApiService;
use App\Services\CollectionBatchService;
use App\Services\BankPayloadService;
use App\Services\CollectionAnalyticsService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use Psy\VersionUpdater\Installer;

class CollectionController extends Controller
{
    public function __construct(
        protected BankPayloadService $payloadService,
        protected BankApiService $bankApi,
        protected DeductionService $deductionService,
        protected CollectionAnalyticsService $collectionAnalytics,
        protected CollectionBatchService $collectionBatchService
    )
    {
        $this->middleware('auth');
    }

    public function batches(Request $request)
    {
        $title = 'Mandate Collection';

        //collection batches
        $number_of_batches = 0;
        $batches = CollectionBatch::filterCondition();
        $statsQuery = CollectionBatch::filterCondition();

        //providers
        $providers = Partner::filterCondition();
        $providers = $providers->get();

        if (isset($_POST['filter'])) {
            //all request
            $requests = $request->all();

            $batches = $batches->when($requests, function ($query) use ($requests) {
                $query->withFilters($requests);
            });

            $statsQuery = $statsQuery->when($requests, function ($query) use ($requests) {
                $query->withFilters($requests);
            });

            $number_of_batches = $batches->count();
            $batches = $batches->get();
        } else {
            $number_of_batches = $batches->count();
            $batches = $batches->paginate(50);
        }

        $listStats = [
            'total' => (clone $statsQuery)->count(),
            'uploaded' => (clone $statsQuery)->where('pushed', 1)->count(),
            'processed' => (clone $statsQuery)->where('processed', 1)->count(),
            'failed' => (clone $statsQuery)->where('failed', 1)->count(),
            'collections' => (clone $statsQuery)->sum('number_of_collections'),
        ];

        $monthlyFailedEligibility = $this->collectionBatchService->lastMonthFailedBatchEligibility();

        $post_data = [
            'start_at' => $request->start_at ?? null,
            'end_at' => $request->end_at ?? null,
            'partner_id' => $request->partner_id ?? null,
            'cl_status' => $request->cl_status ?? null,
            'mandate_reference' => $request->mandate_reference ?? null,
        ];

        //render view
        return view('collections.batches', compact('title', 'batches', 'providers', 'number_of_batches', 'post_data', 'listStats', 'monthlyFailedEligibility'));
    }

    public function create(Request $request)
    {
        $title = 'Create Mandate Collection';

        $limit = 250;
        $partner_id = null;
        $arr_accounts = [];
        $arr_installments = [];

        if (isset($_POST['filter'])) {
            //all request
            $requests = $request->all();
            $partner_id =  $requests['partner_id'];
            $debit_date =  $requests['debit_date'];

            // initialize deduction service
            $partner = Partner::whereHas('debitOrders', function ($query) use ($debit_date) {
                return $query->eligibleForCollection('NMB')
                    ->whereDate('next_debit_date', "=", $debit_date);
            })->where(['partners.id' => $partner_id, 'partners.status' => 202]);

            if ($partner->count() > 0) {
                $partner = $partner->first();
                $arr_installments = $this->collectionBatchService->previewInstallments($partner, $debit_date, 250);

                if (empty($arr_installments)) {
                    return Redirect::route('collections.create')->with('danger', 'No any collection on this ' . $debit_date);
                }
            }
        }

        //users
        $user = User::findOrFail(Auth::user()->id);

        $provider = null;
        if ($user->partners->where('authorize', '=', 1)->count() == 1)
            $provider = $user->partners[0];

        //service providers
        $providers = Partner::filterCondition()->get();

        //maximum date
        $max_date = Carbon::now()->addDays(5)->format('Y-m-d');

        //render view
        return view('collections.create', compact('title', 'max_date', 'providers', 'provider', 'partner_id', 'arr_installments'));
    }

    public function store(Request $request)
    {
        if ($request->selected_partner_id && $request->installment_ids) {
            $batch = $this->collectionBatchService->createManualBatch(
                (int) $request->selected_partner_id,
                array_map('intval', $request->installment_ids),
                Auth::user()
            );

            //Batch Information
            $collection_reference = $batch?->collectionReference;

            //send Notification of batch creation
            if ($batch) {
                $message = "Collection batch number " . $collection_reference . " created. Review and approve to the next stage.";
                $arr_phone = [255717705746, 255656314330];

                foreach ($arr_phone as $phone) {
                    //send sms
                    $data = ["phone" => $phone, "message" => $message];
                    dispatch(new SendSMSJob($data));
                }
            }

            //redirect
            if ($batch) {
                return Redirect::route('collections.create')->with('success', 'Collection batch created, Preview and Approve to next stage.');
            }

            return Redirect::route('collections.create')->with('danger', 'No eligible collection selected.');
        } else {
            //redirect
            return Redirect::route('collections.create')->with('danger', 'No any collection selected.');
        }
    }

    public function show($id)
    {
        $title = "Collection Batch Information";

        //batch
        $batch = CollectionBatch::where(['id' => $id])->firstOrFail();

        // validate the date before creating new collection dates
        $current_month = now()->month; //current month
        $batch_month = Carbon::parse($batch->collectionDate)->format('m');
        $diff = $current_month - $batch_month;

        $show_create_button = false;
        if ($diff == 1)
            $show_create_button = true;

        //installments
        $arr_installments = [];
        $number_of_failed_installments = 0;
        $installment_ids = $batch->installment_id_list;

        $installments = Installment::whereIn('id', $installment_ids);

        if ($installments->count() > 0) {
            //installments
            $installments = $installments->get();

            //count number of failed installments
            $number_of_failed_installments = $installments->filter(function ($installment) {
                return in_array($installment->status, ['FAILED', 'FUNDSCHECK']);
            })->count();

            foreach ($installments as $val) {
                $arr_installments[] = [
                    'id' => $val->id,
                    'reference' => $val->debitOrder->reference,
                    'periodicity' => $val->debitOrder->periodicity,
                    'account_name' => strtoupper($val->debitOrder->customerAccount->account_name),
                    'account_number' => $val->debitOrder->customerAccount->account_number,
                    'amount' => $val->installment_amount,
                    'last_paid_amount' => $val->last_paid_amount,
                    'current_balance' => $val->current_balance,
                    'outstanding_amount' => $val->outstanding_amount,
                    'status' => $val->status,
                    'remarks' => $val->remarks,
                    'payment_date' => Carbon::parse($val->payment_date)->format('d-m-Y'),
                    'end_date' => Carbon::parse($val->debitOrder->end_date)->format('d-m-Y'),
                ];
            }
        }

        //render view
        return view('collections.show', compact('title', 'batch', 'arr_installments', 'show_create_button', 'number_of_failed_installments'));
    }

    //export xls
    public function export_created($id)
    {
        $arr_installments = [];

        //batch
        $batch = CollectionBatch::where(['id' => $id])->firstOrFail();

        //installments
        $installment_ids = $batch->installment_id_list;
        $installments = Installment::whereIn('id', $installment_ids);

        if ($installments->count() > 0) {
            //installments
            $installments = $installments->get();

            foreach ($installments as $val) {
                $arr_installments[] = [
                    'reference' => $val->debitOrder->reference,
                    'periodicity' => $val->debitOrder->periodicity,
                    'account_name' => strtoupper($val->debitOrder->customerAccount->account_name),
                    'account_number' => $val->debitOrder->customerAccount->account_number,
                    'amount' => $val->installment_amount ?? 0,
                    'payment_date' => Carbon::parse($val->payment_date)->format('d-m-Y'),
                    'end_date' => Carbon::parse($val->debitOrder->end_date)->format('d-m-Y'),
                ];
            }
        }

        //create filename
        $filename = $batch->collectionReference . "_" . $batch->partner->business_name . ".xlsx";

        //return
        return Excel::download(new CollectionCreatedExport($arr_installments), $filename);
    }

    //export xls
    public function export_processed($id)
    {
        $arr_installments = [];

        //batch
        $batch = CollectionBatch::where(['id' => $id])->firstOrFail();

        //installments
        $installment_ids = $batch->installment_id_list;
        $installments = Installment::whereIn('id', $installment_ids);

        if ($installments->count() > 0) {
            //installments
            $installments = $installments->get();

            foreach ($installments as $val) {
                // format status
                if ($val->status == "CLOSED")
                    $status = "PAID";
                elseif ($val->status == "FAILED")
                    $status = "UNPAID";
                else
                    $status = strtoupper($val->status);


                $arr_installments[] = [
                    'reference' => $val->debitOrder->reference,
                    'account_name' => strtoupper($val->debitOrder->customerAccount->account_name),
                    'account_number' => $val->debitOrder->customerAccount->account_number,
                    'amount' => $val->installment_amount ?? 0,
                    'last_paid_amount' => $val->last_paid_amount ?? 0,
                    'outstanding_amount' => $val->outstanding_amount,
                    'status' => $status,
                    'remarks' => $val->remarks,
                    'payment_date' => Carbon::parse($val->payment_date)->format('d-m-Y')
                ];
            }
        }

        //create filename
        $filename = $batch->collectionReference . "_" . $batch->partner->business_name . ".xlsx";

        //return
        return Excel::download(new CollectionProcessedExport($arr_installments), $filename);
    }

    public function upload(Request $request)
    {
        $title = "Upload Collection Batch";

        //collection batch
        $collection_batch = CollectionBatch::where(['id' => $request->batch_id])->firstOrFail();

        if ($collection_batch->canBePushed()) {
            dispatch(new PushCollectionJob($collection_batch->id));
        }

        //response
        return response()->json(['success' => true, 'success_msg' => "Collection batch uploaded."], 200);
    }

    public function archive($id)
    {
        $title = 'Archive collection batch';

        //batch
        $batch = CollectionBatch::where(['id' => $id])->firstOrFail();
        $batch->deleted = 1;
        $batch->lifecycle_status = CollectionBatch::LIFECYCLE_ARCHIVED;
        $batch->save();

        //redirect
        return Redirect::route('collections.batches')->with('success', 'Collection batch archived');
    }

    public function create_failed($id = null)
    {
        $title = 'Create Failed Collection Batch';
        $selectedBatchId = $id;

        $batches = CollectionBatch::filterCondition()
            ->where('recreated', 0)
            ->where(function ($query) {
                $query->where('failed', 1)
                    ->orWhere(function ($inner) {
                        $inner->where('processed', 1)->where('number_of_failed', '>', 0);
                    });
            })
            ->orderByDesc('collectionDate')
            ->get()
            ->filter(function ($batch) {
                if (empty($batch->collectionDate)) {
                    return false;
                }

                $currentMonth = now()->month;
                $batchMonth = (int) Carbon::parse($batch->collectionDate)->format('m');

                return ($currentMonth - $batchMonth) === 1;
            })
            ->values();

        $rows = $batches->map(function ($batch) {
            $installments = Installment::whereIn('id', $batch->installment_id_list)->get();
            $failedCount = $installments->whereIn('status', ['FAILED', 'FUNDSCHECK'])->count();

            return [
                'id' => $batch->id,
                'collectionReference' => $batch->collectionReference,
                'partner_name' => $batch->partner->business_name ?? 'NIL',
                'collectionDate' => $batch->collectionDate,
                'created_at' => $batch->created_at,
                'number_of_collections' => $batch->number_of_collections ?? 0,
                'number_of_failed' => $batch->number_of_failed ?: $failedCount,
                'remarks' => $batch->remarks,
            ];
        });

        return view('collections.create_failed', compact('title', 'rows', 'selectedBatchId'));
    }

    public function store_failed(Request $request)
    {
        $batchIds = collect($request->input('batch_ids', []))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values();

        if ($batchIds->isEmpty()) {
            return Redirect::route('collections.create-failed')->with('danger', 'Select at least one batch to recreate.');
        }

        $success = 0;
        $references = [];

        foreach ($batchIds as $batchId) {
            $collectionBatch = CollectionBatch::find($batchId);

            if (!$collectionBatch) {
                continue;
            }

            $batch = $this->collectionBatchService->recreateFailedBatch($collectionBatch, Auth::user());

            if ($batch) {
                $success++;
                $references[] = $batch->collectionReference;
            }
        }

        if ($success === 0) {
            return Redirect::route('collections.create-failed')->with('warning', 'No failed collection batch was created from the selected rows.');
        }

        $message = "Collection batch" . ($success > 1 ? 'es' : '') . " " . implode(', ', $references) . " created. Review and approve to the next stage.";
        $arr_phone = [255717705746, 255656314330];

        foreach ($arr_phone as $phone) {
            $data = ["phone" => $phone, "message" => $message];
            dispatch(new SendSMSJob($data));
        }

        return Redirect::route('collections.batches')->with('success', "Created {$success} failed collection batch(es) successfully.");
    }

    public function create_monthly_failed()
    {
        $previousMonth = now()->subMonth();
        $eligibility = $this->collectionBatchService->lastMonthFailedBatchEligibility();

        if (!$eligibility['allowed']) {
            return Redirect::route('collections.batches')->with('warning', $eligibility['reason']);
        }

        $count = $this->collectionBatchService->recreateFailedBatchesForMonth(
            (int) $previousMonth->format('m'),
            (int) $previousMonth->format('Y'),
            Auth::user()
        );

        if ($count === 0) {
            return Redirect::route('collections.batches')->with('warning', 'No failed collection batches were eligible for monthly recreation.');
        }

        return Redirect::route('collections.batches')->with('success', "Created {$count} failed collection batch(es) for " . $previousMonth->format('F Y') . '.');
    }

    public function fetch_status(Request $request)
    {
        $title = "Fetch Collection Status";

        //Collection batch
        $collection_batch = CollectionBatch::where(['id' => $request->batch_id])->firstOrFail();

        if ($collection_batch->canFetchStatus()) {
            dispatch(new CollectionStatusJob($collection_batch->id));
        }

        //response
        return response()->json(['success' => true, 'success_msg' => "Collection status successful fetched."], 200);
    }

    public function expected_monthly(Request $request)
    {
        $post_data = $this->collectionAnalytics->defaultFilters(CollectionAnalyticsService::PERIOD_MONTHLY, $request->all());
        $summary = $this->collectionAnalytics->summary(
            CollectionAnalyticsService::TYPE_EXPECTED,
            CollectionAnalyticsService::PERIOD_MONTHLY,
            $post_data
        );

        $display_current_month = date('F', mktime(0, 0, 0, $post_data['month'], 1));
        $title = "Expected Monthly Collection - {$display_current_month}, {$post_data['year']}";
        $payment_channels = $this->collectionAnalytics->paymentChannels();
        $arr_data = $summary['rows'];
        $listStats = $summary['stats'];
        $collectionType = CollectionAnalyticsService::TYPE_EXPECTED;
        $collectionPeriod = CollectionAnalyticsService::PERIOD_MONTHLY;

        return view('collections.expected_collection_monthly', compact(
            'title',
            'payment_channels',
            'arr_data',
            'post_data',
            'listStats',
            'collectionType',
            'collectionPeriod'
        ));
    }

    public function expected_monthly_detail($uuid, Request $request)
    {
        $post_data = $this->collectionAnalytics->defaultFilters(CollectionAnalyticsService::PERIOD_MONTHLY, $request->all());
        $detail = $this->collectionAnalytics->detail(
            CollectionAnalyticsService::TYPE_EXPECTED,
            CollectionAnalyticsService::PERIOD_MONTHLY,
            $uuid,
            $post_data
        );

        $partner = $detail['partner'];
        $arr_installments = $detail['rows'];
        $listStats = $detail['stats'];
        $display_current_month = date('F', mktime(0, 0, 0, $post_data['month'], 1));
        $title = "Expected Monthly Collection - {$display_current_month}, {$post_data['year']}";
        $collectionType = CollectionAnalyticsService::TYPE_EXPECTED;
        $collectionPeriod = CollectionAnalyticsService::PERIOD_MONTHLY;

        return view('collections.expected_collection_monthly_detail', compact(
            'title',
            'partner',
            'arr_installments',
            'post_data',
            'listStats',
            'collectionType',
            'collectionPeriod'
        ));
    }

    public function export_expected_monthly($uuid, Request $request)
    {
        $post_data = $this->collectionAnalytics->defaultFilters(CollectionAnalyticsService::PERIOD_MONTHLY, $request->all());
        $partner = Partner::where(['uuid' => $uuid])->firstOrFail();
        $arr_installments = $this->collectionAnalytics->exportRows(
            CollectionAnalyticsService::TYPE_EXPECTED,
            CollectionAnalyticsService::PERIOD_MONTHLY,
            $uuid,
            $post_data
        );
        $filename = "Expected_Collection_" . date('F', mktime(0, 0, 0, $post_data['month'], 1)) . "_" . $partner->business_name . ".xlsx";

        return Excel::download(new CollectionPayments($arr_installments), $filename);
    }

    public function actual_monthly(Request $request)
    {
        $post_data = $this->collectionAnalytics->defaultFilters(CollectionAnalyticsService::PERIOD_MONTHLY, $request->all());
        $summary = $this->collectionAnalytics->summary(
            CollectionAnalyticsService::TYPE_ACTUAL,
            CollectionAnalyticsService::PERIOD_MONTHLY,
            $post_data
        );

        $display_current_month = date('F', mktime(0, 0, 0, $post_data['month'], 1));
        $title = "Actual Monthly Collection - {$display_current_month}, {$post_data['year']}";
        $payment_channels = $this->collectionAnalytics->paymentChannels();
        $arr_data = $summary['rows'];
        $listStats = $summary['stats'];
        $collectionType = CollectionAnalyticsService::TYPE_ACTUAL;
        $collectionPeriod = CollectionAnalyticsService::PERIOD_MONTHLY;

        return view('collections.actual_collection_monthly', compact(
            'title',
            'payment_channels',
            'arr_data',
            'post_data',
            'listStats',
            'collectionType',
            'collectionPeriod'
        ));
    }

    public function actual_monthly_detail($uuid, Request $request)
    {
        $post_data = $this->collectionAnalytics->defaultFilters(CollectionAnalyticsService::PERIOD_MONTHLY, $request->all());
        $detail = $this->collectionAnalytics->detail(
            CollectionAnalyticsService::TYPE_ACTUAL,
            CollectionAnalyticsService::PERIOD_MONTHLY,
            $uuid,
            $post_data
        );

        $partner = $detail['partner'];
        $arr_installments = $detail['rows'];
        $listStats = $detail['stats'];
        $display_current_month = date('F', mktime(0, 0, 0, $post_data['month'], 1));
        $title = "Actual Monthly Collection - {$display_current_month}, {$post_data['year']}";
        $collectionType = CollectionAnalyticsService::TYPE_ACTUAL;
        $collectionPeriod = CollectionAnalyticsService::PERIOD_MONTHLY;

        return view('collections.actual_collection_monthly_detail', compact(
            'title',
            'partner',
            'arr_installments',
            'post_data',
            'listStats',
            'collectionType',
            'collectionPeriod'
        ));
    }

    public function export_actual_monthly($uuid, Request $request)
    {
        $post_data = $this->collectionAnalytics->defaultFilters(CollectionAnalyticsService::PERIOD_MONTHLY, $request->all());
        $partner = Partner::where(['uuid' => $uuid])->firstOrFail();
        $arr_installments = $this->collectionAnalytics->exportRows(
            CollectionAnalyticsService::TYPE_ACTUAL,
            CollectionAnalyticsService::PERIOD_MONTHLY,
            $uuid,
            $post_data
        );
        $current_month_year = date('F', mktime(0, 0, 0, $post_data['month'], 1)) . '_' . $post_data['year'];
        $filename = "Actual_Collection_" . $current_month_year . "_" . $partner->business_name . ".xlsx";

        return Excel::download(new CollectionPayments($arr_installments), $filename);
    }

    public function expected_daily(Request $request)
    {
        $post_data = $this->collectionAnalytics->defaultFilters(CollectionAnalyticsService::PERIOD_DAILY, $request->all());
        $summary = $this->collectionAnalytics->summary(
            CollectionAnalyticsService::TYPE_EXPECTED,
            CollectionAnalyticsService::PERIOD_DAILY,
            $post_data
        );

        $title = 'Expected Daily Collection - ' . Carbon::parse($post_data['payment_at'])->format('d F, Y');
        $payment_channels = $this->collectionAnalytics->paymentChannels();
        $arr_data = $summary['rows'];
        $listStats = $summary['stats'];
        $collectionType = CollectionAnalyticsService::TYPE_EXPECTED;
        $collectionPeriod = CollectionAnalyticsService::PERIOD_DAILY;

        return view('collections.expected_collection_daily', compact(
            'title',
            'payment_channels',
            'arr_data',
            'post_data',
            'listStats',
            'collectionType',
            'collectionPeriod'
        ));
    }

    public function expected_daily_detail($uuid, Request $request)
    {
        $post_data = $this->collectionAnalytics->defaultFilters(CollectionAnalyticsService::PERIOD_DAILY, $request->all());
        $detail = $this->collectionAnalytics->detail(
            CollectionAnalyticsService::TYPE_EXPECTED,
            CollectionAnalyticsService::PERIOD_DAILY,
            $uuid,
            $post_data
        );

        $partner = $detail['partner'];
        $arr_installments = $detail['rows'];
        $listStats = $detail['stats'];
        $title = 'Expected Daily Collection - ' . date('d F, Y', strtotime($post_data['payment_at'])) . "";
        $collectionType = CollectionAnalyticsService::TYPE_EXPECTED;
        $collectionPeriod = CollectionAnalyticsService::PERIOD_DAILY;

        return view('collections.expected_collection_daily_detail', compact(
            'title',
            'partner',
            'arr_installments',
            'post_data',
            'listStats',
            'collectionType',
            'collectionPeriod'
        ));
    }

    public function export_expected_daily($uuid, Request $request)
    {
        $post_data = $this->collectionAnalytics->defaultFilters(CollectionAnalyticsService::PERIOD_DAILY, $request->all());
        $partner = Partner::where(['uuid' => $uuid])->firstOrFail();
        $arr_installments = $this->collectionAnalytics->exportRows(
            CollectionAnalyticsService::TYPE_EXPECTED,
            CollectionAnalyticsService::PERIOD_DAILY,
            $uuid,
            $post_data
        );
        $filename = "Expected_Collection_" . date('d_m_Y', strtotime($post_data['payment_at'])) . "_" . $partner->business_name . ".xlsx";

        return Excel::download(new CollectionPayments($arr_installments), $filename);
    }

    public function actual_daily(Request $request)
    {
        $post_data = $this->collectionAnalytics->defaultFilters(CollectionAnalyticsService::PERIOD_DAILY, $request->all());
        $summary = $this->collectionAnalytics->summary(
            CollectionAnalyticsService::TYPE_ACTUAL,
            CollectionAnalyticsService::PERIOD_DAILY,
            $post_data
        );

        $title = 'Actual Daily Collection - ' . Carbon::parse($post_data['payment_at'])->format('d F, Y');
        $payment_channels = $this->collectionAnalytics->paymentChannels();
        $arr_data = $summary['rows'];
        $listStats = $summary['stats'];
        $collectionType = CollectionAnalyticsService::TYPE_ACTUAL;
        $collectionPeriod = CollectionAnalyticsService::PERIOD_DAILY;

        return view('collections.actual_collection_daily', compact(
            'title',
            'payment_channels',
            'arr_data',
            'post_data',
            'listStats',
            'collectionType',
            'collectionPeriod'
        ));
    }

    public function actual_daily_detail($uuid, Request $request)
    {
        $post_data = $this->collectionAnalytics->defaultFilters(CollectionAnalyticsService::PERIOD_DAILY, $request->all());
        $detail = $this->collectionAnalytics->detail(
            CollectionAnalyticsService::TYPE_ACTUAL,
            CollectionAnalyticsService::PERIOD_DAILY,
            $uuid,
            $post_data
        );

        $partner = $detail['partner'];
        $arr_installments = $detail['rows'];
        $listStats = $detail['stats'];
        $title = 'Actual Daily Collection - ' . date('d F, Y', strtotime($post_data['payment_at'])) . "";
        $collectionType = CollectionAnalyticsService::TYPE_ACTUAL;
        $collectionPeriod = CollectionAnalyticsService::PERIOD_DAILY;

        return view('collections.actual_collection_daily_detail', compact(
            'title',
            'partner',
            'arr_installments',
            'post_data',
            'listStats',
            'collectionType',
            'collectionPeriod'
        ));
    }

    public function export_actual_daily($uuid, Request $request)
    {
        $post_data = $this->collectionAnalytics->defaultFilters(CollectionAnalyticsService::PERIOD_DAILY, $request->all());
        $partner = Partner::where(['uuid' => $uuid])->firstOrFail();
        $arr_installments = $this->collectionAnalytics->exportRows(
            CollectionAnalyticsService::TYPE_ACTUAL,
            CollectionAnalyticsService::PERIOD_DAILY,
            $uuid,
            $post_data
        );
        $filename = "Actual_Collection_" . date('d_m_Y', strtotime($post_data['payment_at'])) . "_" . $partner->business_name . ".xlsx";

        return Excel::download(new CollectionPayments($arr_installments), $filename);
    }

    public function reconciliation(Request $request)
    {
        //read file
        $file = fopen(public_path('assets/reports/03/Waningo_MicroCredit_31110037602.csv'), 'r');
        fgetcsv($file); // Skip the first row

        //array of installments
        $arr_installments = [];

        //iterarate file
        while (($row = fgetcsv($file, 10000, ',')) !== false) {
            $mandate_ref = substr($row[3], -12);
            $paid_amount = $row[6];

            if (empty($mandate_ref))
                continue;

            //mandate 
            $debit_order = DebitOrder::where(["reference" => $mandate_ref])->first();

            if (!$debit_order)
                continue;

            //query installment which are failed
            $installment = Installment::where(['debit_order_id' => $debit_order->id])
                ->whereMonth('payment_date', '03')->first();

            //installmentAmount
            $installmentAmount = $installment->installment_amount ?? $installment->max_installment_amount;

            //create array
            $arr_installments[] = [
                'installmentId' => $installment->id,
                'mandateReference' => $mandate_ref,
                'accountName' => strtoupper($debit_order->customerAccount->account_name),
                'accountNumber' => $debit_order->customerAccount->account_number,
                'paidAmount' => $paid_amount,
                'installmentAmount' => $installmentAmount,
                'paymentDate' => $installment->payment_date,
                'NMB Status' => "PAID",
                "eMakato status" => $installment->status
            ];

            //process calculation for installment and debit order.
            // if ($installment && $installment->status != "CLOSED") {
            //     //update installment
            //     $installment->last_paid_amount = $paid_amount;
            //     $installment->save();

            //     //process installment
            //     $process_data = $this->deductionService->processInstallment($installment, $paid_amount, $mandate_ref);
            // }
        }
        //fclose($file);

        // echo "<pre>";
        // print_r($arr_installments);

        //response
        return response()->json(['success' => false, 'success_msg' => "File Processed"]);
    }

    public function reconciliation_crdb(Request $request)
    {
        //read file
        $file = fopen(public_path('assets/reports/CRDB/Jan_2026.csv'), 'r');
        fgetcsv($file); // Skip the first row

        //iterarate file
        $success = 0;
        while (($row = fgetcsv($file, 10000, ',')) !== false) {
            $collectionDate = $row[0];
            $transactionReference = $row[1];
            $amount = floatval(str_replace(',', '', $row[3]));
            $mandateReference = $row[4];

            if (empty($mandateReference))
                continue;

            Log::debug("Mandate Reference: $mandateReference");

            //mandate 
            $debitOrder = DebitOrder::where(["reference" => $mandateReference])->first();

            if (!$debitOrder)
                continue;

            Log::debug("== debit order found ==");

            // check if payment reference exists in installments payment
            $installment_payments = InstallmentPayment::where([
                'mandate_reference' => $debitOrder->reference,
                'payment_reference' => $transactionReference
            ]);

            if (!$installment_payments->exists()) {
                Log::debug("== installment payment does not exist ==");

                // create installment payment
                $installment_payment = new InstallmentPayment();
                $installment_payment->mandate_reference = $debitOrder->reference;
                $installment_payment->payment_reference = $transactionReference;
                $installment_payment->amount = floatval($amount); //$amount;
                $installment_payment->payment_at = date('Y-m-d', strtotime($collectionDate));
                $installment_payment->save();

                // check if installment  exists
                $installments = Installment::where('debit_order_id', "=", $debitOrder->id)
                    ->whereMonth('payment_date', "=", date('m', strtotime($collectionDate)))
                    ->whereYear('payment_date', "=", date('Y', strtotime($collectionDate)));

                if ($installments->exists()) {
                    // get installment
                    $installment = $installments->first();

                    //updated installment
                    $installment->last_paid_amount = $amount;
                    $installment->payment_reference = $transactionReference;
                    $installment->effective_payment_date = date('Y-m-d', strtotime($collectionDate));
                    $installment->remarks = "Amount paid";
                    $installment->save();

                    // process installment
                    $this->deductionService->processInstallment($installment);
                } else {
                    //1.0 calculate installment amount
                    $installment_amount = $this->deductionService->getInstallmentAmount($debitOrder);
                    $last_installment_order = $this->deductionService->getLastInstallmentOrder($debitOrder);

                    //1.0 create new installment with payment details
                    $new_installment = new Installment();
                    $new_installment->debit_order_id = $debitOrder->id;
                    $new_installment->installment_order = $last_installment_order + 1;
                    $new_installment->installment_amount = $installment_amount;
                    $new_installment->payment_date = date('Y-m-d', strtotime($collectionDate));
                    $new_installment->last_paid_amount = $amount;
                    $new_installment->payment_reference = $transactionReference;
                    $new_installment->effective_payment_date = date('Y-m-d', strtotime($collectionDate));
                    $new_installment->remarks = "Amount paid";
                    $new_installment->save();

                    //2.0 then process payment installment
                    $this->deductionService->processInstallment($new_installment);
                }
                $success++;
            } else {
                Log::debug("== installment payment exists ==");
            }
        }

        //response
        return response()->json(['success' => true, 'success_msg' => "File Processed", 'success_count' => $success]);
    }

    public function update_collection_status(Request $request)
    {
        //read file
        $file = fopen(public_path('assets/reports/05/Modified_Batch_RN.csv'), 'r');
        fgetcsv($file); // Skip the first row

        //array of installments
        $arr_installments = [];

        //iterarate file
        while (($row = fgetcsv($file, 10000, ',')) !== false) {
            $mandateRef = $row[0];
            $status = $row[2];
            $collDate = $row[3];

            if (empty($mandateRef) || $status != "PAID")
                continue;

            //mandate 
            $debit_order = DebitOrder::where(["reference" => $mandateRef])->first();

            if (!$debit_order)
                continue;

            //query installment which are failed
            $installment = Installment::where(['debit_order_id' => $debit_order->id])
                ->whereMonth('payment_date', '05')->first();

            if (!$installment)
                continue;

            # Query installment for next month
            $next_month_installment = Installment::where(['debit_order_id' => $debit_order->id, 'status' => 'OPEN'])
                ->whereMonth('payment_date', '06')->first();

            if ($next_month_installment) {
                //update next month installment status to "CLOSED"
                $next_month_installment->status = "CLOSED";
                $next_month_installment->remarks = "Account Is Valid";
                $next_month_installment->current_balance = $installment->current_balance;
                $next_month_installment->outstanding_amount = 0;
                $next_month_installment->last_paid_amount = $installment->last_paid_amount;
                $next_month_installment->pushed = 1;
                $next_month_installment->created = 1;
                $next_month_installment->save();

                //update this month installment status to "FUNDSCHECK"  
                $installment->status = "FUNDSCHECK";
                $installment->remarks = "Insufficient Funds";
                $installment->current_balance = 0;
                $installment->outstanding_amount = $installment->installment_amount;
                $installment->last_paid_amount = 0;
                $installment->save();
            }
        }
        fclose($file);

        //response
        return response()->json(['success' => false, 'success_msg' => "File Processed"]);
    }

    public function create_collection_values(Request $request)
    {
        $filename = 'collection_value_06.csv';
        $folderPath = public_path('assets/reports/05');

        // Ensure the folder exists
        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0775, true);
        }

        $filePath = $folderPath . '/' . $filename;

        //select active debit orders
        $debit_orders = DebitOrder::where(['approved' => 1, 'deleted' => 0, 'status' => 240, 'debit_type' => 'FIXED'])
            ->where('end_date', '>=', now())->whereBetween("id", [4701, 4800])->get();

        // Open file handle
        $handle = fopen($filePath, 'w');

        foreach ($debit_orders as $debit_order) {
            //summation of installment amounts
            $total_installment_amount = Installment::where(['debit_order_id' => $debit_order->id])
                ->sum('installment_amount');

            if ($total_installment_amount != $debit_order->total_amount) {
                //find installment where installment amount is not equal to debit order installment amount
                $installments = Installment::where(['debit_order_id' => $debit_order->id])
                    ->where('installment_amount', '!=', $debit_order->installment_amount)
                    ->where('status', '!=', 'CLOSED')
                    ->latest('id')->count();

                if (!$installments)
                    continue;

                if ($installments == 1)
                    fputcsv($handle, [$debit_order->reference]);

                echo "TI: {$total_installment_amount} TD: {$debit_order->total_amount} -- Reference: {$debit_order->reference} - Installments: {$installments}<br>";
            }
        }
        fclose($handle);
    }

    public function update_collection_values(Request $request)
    {
        //read file
        $file = fopen(public_path('assets/reports/05/collection_value_06.csv'), 'r');
        fgetcsv($file); // Skip the first row

        //iterarate file
        while (($row = fgetcsv($file, 10000, ',')) !== false) {
            $mandateRef = $row[0];

            if (empty($mandateRef))
                continue;

            //mandate 
            $debit_order = DebitOrder::where(["reference" => $mandateRef])->first();

            if (!$debit_order)
                continue;

            // Get the last 2 installments for this debit_order_id, ordered by created_at or id
            $installments = Installment::where('debit_order_id', $debit_order->id)
                ->orderBy('installment_order', 'desc')
                ->take(2)
                ->get();

            // Check if we have at least 2 records
            if ($installments->count() == 2) {
                // Since we ordered descending, first is latest, second is before latest
                $latest = $installments[0];
                $previous = $installments[1];

                // Calculate difference
                $difference = $previous->installment_amount - $latest->installment_amount;

                // Update latest installment
                $latest->installment_amount = $difference;
                $latest->save();
            }
        }

        return response()->json(['status' => 'success']);
    }

    public function create_debit_orders()
    {
        $batches = CollectionBatch::where(['deleted' => 0])->get();

        foreach ($batches as $batch) {
            //installments
            $installment_ids = explode(',', $batch->installment_ids);
            $installments = Installment::whereIn('id', $installment_ids);

            if ($installments->count() > 0) {
                //installments
                $installments = $installments->get();

                foreach ($installments as $installment) {
                    if ($installment->status == 'CLOSED' || $installment->status == 'SUCCESS')
                        $status = 'SUCCESS';
                    else
                        $status = 'CREATED';

                    //create or update collection batch
                    $collection_debit_order = CollectionDebitOrder::firstOrNew(
                        ['debit_order_id' => $installment->debitOrder->id, 'payment_at' => $installment->payment_date]
                    );
                    $collection_debit_order->collection_batch_id = $batch->id;
                    $collection_debit_order->status = $status;
                    $collection_debit_order->save();
                }
            }
        }

        return response()->json(['error' => false, 'message' => 'Collection debit orders created']);
    }

    public function update_debit_orders()
    {
        $current_month = now()->month; //current month
        $current_year = now()->year; // current year
        $debit_orders = DebitOrder::where(['approved' => 1])->get(); //debit orders

        foreach ($debit_orders as $val) {
            $payment_month = Carbon::parse($val->next_debit_date)->format('m');

            if ($payment_month != $current_month) {
                //original date
                $original = Carbon::parse($val->next_debit_date);

                //new date
                $newDate = Carbon::create($current_year, $current_month, $original->day);

                //update debit order 
                $debit_order = DebitOrder::findOrFail($val->id);
                $debit_order->next_debit_date = $newDate;
                $debit_order->save();
            }
        }

        return response()->json(['error' => false, 'success' => 'Debit order updated']);
    }

    //delete collection batch
    public function delete_collection_batch()
    {
        $collectionReference = "10576";

        //collection batch
        $collection_batch = CollectionBatch::where(['collectionReference' => $collectionReference])->firstOrFail();

        //installment_ids
        $installment_ids = explode(",", $collection_batch->installment_ids);

        //installments
        $installments = Installment::whereIn('id', $installment_ids)->get();

        foreach ($installments as $installment) {
            $debit_order_id = $installment->debitOrder->id;
            $installment_id = $installment->id;
            $payment_date = $installment->debitOrder->next_debit_date;

            //4.0: check if the mandate reference debited this month
            $collection_debit_order = CollectionDebitOrder::where(['debit_order_id' => $debit_order_id, 'status' => 'CREATED'])
                ->whereMonth('payment_at', Carbon::parse($payment_date)->format('m'));


            if ($collection_debit_order->count() > 0) {
                $collection_debit_order = $collection_debit_order->first();
                // echo "<pre>";
                // print_r($collection_debit_order->toArray());

                //delete collection debit order
                $collection_debit_order->delete();
            }
        }

        //delete collection batch
        $collection_batch->deleted = 1;
        $collection_batch->save();

        return response()->json(['error' => false, 'success_msg' => 'Collection batch deleted successfully']);
    }

    //update collection date
    public function update_collection_date()
    {
        $reference = [10957, 10964, 10965, 10966, 10967, 10968, 10969, 10961, 10970, 10972, 10973, 10974, 10975, 10976, 10977, 10978, 10979, 10980, 10981, 10982, 10983, 10984, 10985, 10986, 10987, 10988, 10989, 10990, 10991, 10992, 10993, 10994, 10995, 10996, 10997, 10998, 10999, 11000, 11001, 11002, 11003, 11004, 11005, 11006, 11008, 11009, 11010, 11011, 11016, 11017, 11018];
        $batches = CollectionBatch::select('id', 'installment_ids')->whereIn('collectionReference', $reference)->get();

        foreach ($batches as $val) {
            $installment_ids = explode(",", $val->installment_ids);

            foreach ($installment_ids as $installment_id) {
                //get installment
                $installment = Installment::where(['id' => $installment_id])->first();

                if (!$installment)
                    continue;

                if ($installment->status == "CLOSED") {
                    //update installment payment date
                    $collection_date = Carbon::parse($installment->payment_date)->addMonth();
                    $installment->effective_payment_date = $collection_date;
                    $installment->save();

                    Log::info("New Payment Date (CLOSED): {$collection_date} for Installment ID: {$installment->id}");
                } else if ($installment->status == "FAILED" && $installment->remarks == "Account Is Valid") {
                    //update installment payment date
                    $collection_date = Carbon::parse($installment->payment_date)->addMonth();
                    $installment->effective_payment_date = $collection_date;
                    $installment->save();

                    Log::info("New Payment Date (FAILED): {$collection_date}");
                }
            }
        }

        return response()->json(['error' => false, 'success_msg' => 'Collection date updated']);
    }
}
