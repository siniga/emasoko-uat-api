<?php

namespace App\Http\Controllers;

use App\Classes\Authentication;
use App\Classes\Messaging;
use App\Http\Requests\CompanyDetailRequest;
use App\Http\Requests\CompanyStaffRequest;
use App\Models\Partner;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use App\Services\CompanyService;
use App\Services\CompanySummaryService;
use App\Services\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SelfCompanyRegistrationController extends CompanyController
{
    private const SESSION_KEY = 'self_registration_partner_uuid';
    private const RECAPTCHA_SESSION_KEY = 'self_registration_recaptcha_verified_uuid';

    public function __construct(
        CompanyService $companyService,
        CompanySummaryService $companySummaryService,
        BankPayloadService $payloadService,
        Messaging $messaging,
        BankApiService $bankApi,
        Authentication $auth,
        protected RecaptchaService $recaptchaService
    ) {
        $this->companyService = $companyService;
        $this->companySummaryService = $companySummaryService;
        $this->payloadService = $payloadService;
        $this->messaging = $messaging;
        $this->bankApi = $bankApi;
        $this->auth = $auth;

        View::share('selfRegistration', true);
    }

    public function registrationForm($uuid = null)
    {
        if ($uuid !== null) {
            $this->assertDraftAccess($uuid);
        }

        return parent::registrationForm($uuid);
    }

    public function storeCompanyDetail(CompanyDetailRequest $request)
    {
        $uuid = $request->input('partner_uuid');

        if ($uuid) {
            $this->assertDraftAccess($uuid);
        }

        if (!$this->recaptchaService->isConfigured()) {
            return response()->json([
                'message' => 'Google reCAPTCHA is not configured. Please contact support.',
            ], 503);
        }

        $request->validate([
            'g-recaptcha-response' => ['required', 'string'],
        ], [
            'g-recaptcha-response.required' => 'Please confirm that you are not a robot.',
        ]);

        if (!$this->recaptchaService->verify(
            (string) $request->input('g-recaptcha-response'),
            $request->ip()
        )) {
            return response()->json([
                'message' => 'Robot verification failed or expired. Please try again.',
                'errors' => [
                    'g-recaptcha-response' => [
                        'Robot verification failed or expired. Please try again.',
                    ],
                ],
            ], 422);
        }

        $request->attributes->set(
            'registration_source',
            Partner::REGISTRATION_SOURCE_SELF_WEB
        );

        $response = parent::storeCompanyDetail($request);
        $payload = $response->getData(true);

        if (($payload['success'] ?? false) && !empty($payload['uuid'])) {
            $request->session()->put(self::SESSION_KEY, (string) $payload['uuid']);
            $request->session()->put(
                self::RECAPTCHA_SESSION_KEY,
                (string) $payload['uuid']
            );
        }

        return $response;
    }

    public function companyDocumentsForm($uuid)
    {
        $this->assertDraftAccess($uuid);

        return parent::companyDocumentsForm($uuid);
    }

    public function storeCompanyDocuments(Request $request)
    {
        $this->assertRequestAccess($request);

        return parent::storeCompanyDocuments($request);
    }

    public function companyStaffForm($uuid)
    {
        $this->assertDraftAccess($uuid);

        return parent::companyStaffForm($uuid);
    }

    public function storeCompanyStaff(CompanyStaffRequest $request)
    {
        $this->assertRequestAccess($request);

        return parent::storeCompanyStaff($request);
    }

    public function companyPreview($uuid)
    {
        $this->assertDraftAccess($uuid);

        return parent::companyPreview($uuid);
    }

    public function savingRegistration(Request $request)
    {
        $this->assertRequestAccess($request);

        return parent::savingRegistration($request);
    }

    public function cancelSelfRegistration(Request $request)
    {
        $partner = $this->assertRequestAccess($request);
        $this->companyService->deleteRegistration($partner);

        $request->session()->forget([
            self::SESSION_KEY,
            self::RECAPTCHA_SESSION_KEY,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration cancelled and all submitted information deleted.',
        ]);
    }

    public function confirmRegistration(Request $request)
    {
        $partner = $this->assertRequestAccess($request);

        if ($request->session()->get(self::RECAPTCHA_SESSION_KEY) !== $request->input('uuid')) {
            return response()->json([
                'message' => 'Robot verification is required. Return to the Information step and try again.',
            ], 422);
        }

        DB::transaction(function () use ($partner) {
            $partner->next_step = "service-provider/register/{$partner->uuid}/preview";
            $partner->status = 'pending';
            $partner->lifecycle_status = Partner::LIFECYCLE_SUBMITTED;
            $partner->complete = 1;
            $partner->updated_by = null;
            $partner->save();
        });

        try {
            $this->companyService->sendAssessmentAlert($partner->fresh());
        } catch (\Throwable $exception) {
            Log::error('Self-registered provider submitted, but reviewer notification failed.', [
                'partner_id' => $partner->id,
                'partner_uuid' => $partner->uuid,
                'exception' => $exception->getMessage(),
            ]);
        }

        $request->session()->forget([
            self::SESSION_KEY,
            self::RECAPTCHA_SESSION_KEY,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service provider information submitted successfully.',
            'uuid' => $partner->uuid,
        ]);
    }

    public function success(Request $request)
    {
        $saved = $request->query('status') === 'saved';
        $resumeUuid = $saved && session(self::SESSION_KEY) === $request->query('uuid')
            ? $request->query('uuid')
            : null;

        return view('companies.create.success', compact('saved', 'resumeUuid'));
    }

    private function assertRequestAccess(Request $request): Partner
    {
        $uuid = (string) ($request->input('partner_uuid') ?: $request->input('uuid'));
        $partner = $this->assertDraftAccess($uuid);

        if ($request->filled('partner_id') && (int) $request->input('partner_id') !== (int) $partner->id) {
            throw new NotFoundHttpException();
        }

        return $partner;
    }

    private function assertDraftAccess(string $uuid): Partner
    {
        if ($uuid === '' || session(self::SESSION_KEY) !== $uuid) {
            throw new NotFoundHttpException();
        }

        return Partner::query()
            ->where('uuid', $uuid)
            ->where('registration_source', Partner::REGISTRATION_SOURCE_SELF_WEB)
            ->where(function ($query) {
                $query->where('complete', 0)
                    ->orWhereNull('complete');
            })
            ->firstOrFail();
    }
}
