<?php

namespace Tests\Unit;

use App\Models\DebitOrder;
use App\Models\DebitOrderRequest;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use App\Services\DeductionService;
use App\Services\CustomerSmsNotificationService;
use App\Services\ExternalCallbackService;
use App\Services\MandateActionService;
use App\Services\PaymentMandateWorkflowService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class PaymentMandateCancellationWorkflowTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_approved_cancellation_is_only_marked_cancelled_after_provider_success(): void
    {
        [$service, $actionService, $callbackService] = $this->workflow();
        [$request, $debitOrder] = $this->cancellationRequest();

        $actionService->shouldReceive('cancelMandate')
            ->once()
            ->with(Mockery::on(function (DebitOrder $order) {
                return $order->lifecycle_status === DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED
                    && (int) $order->cancelled === 0;
            }))
            ->andReturn([
                'success' => true,
                'response_code' => 201,
                'provider_response_code' => 1000,
                'remarks' => 'Mandate cancelled successfully.',
            ]);

        $callbackService->shouldReceive('dispatchMandateStatus')
            ->once()
            ->with($debitOrder, 'MANDATE_CANCELLED');

        $service->approveCancellation($request, 9);

        $this->assertSame(DebitOrder::LIFECYCLE_CANCELLED, $debitOrder->lifecycle_status);
        $this->assertSame(1, $debitOrder->cancelled);
    }

    public function test_provider_failure_does_not_mark_approved_cancellation_as_cancelled(): void
    {
        [$service, $actionService, $callbackService] = $this->workflow();
        [$request, $debitOrder] = $this->cancellationRequest();

        $actionService->shouldReceive('cancelMandate')
            ->once()
            ->andReturn([
                'success' => false,
                'response_code' => 500,
                'provider_response_code' => 1001,
                'remarks' => 'Cancellation rejected.',
            ]);

        $callbackService->shouldNotReceive('dispatchMandateStatus');

        $service->approveCancellation($request, 9);

        $this->assertSame(DebitOrder::LIFECYCLE_FAILED, $debitOrder->lifecycle_status);
        $this->assertSame(0, $debitOrder->cancelled);
    }

    public function test_transport_failure_preserves_current_mandate_status(): void
    {
        [$service, $actionService, $callbackService] = $this->workflow();
        $debitOrder = $this->directCancellationMandate();

        $actionService->shouldReceive('cancelMandate')
            ->once()
            ->andReturn([
                'success' => false,
                'response_code' => 504,
                'provider_response_code' => null,
                'provider_responded' => false,
                'remarks' => 'The bank gateway did not return a valid response. Please try again.',
            ]);

        $callbackService->shouldNotReceive('dispatchMandateStatus');

        $result = $service->cancel($debitOrder, 9, 'Customer request.');

        $this->assertFalse($result['success']);
        $this->assertFalse($result['provider_responded']);
        $this->assertSame(DebitOrder::LIFECYCLE_ACTIVE, $debitOrder->lifecycle_status);
        $this->assertSame(0, $debitOrder->cancelled);
        $this->assertSame('Mandate active.', $debitOrder->remarks);
    }

    public function test_confirmed_provider_rejection_updates_lifecycle_to_failed(): void
    {
        [$service, $actionService, $callbackService] = $this->workflow();
        $debitOrder = $this->directCancellationMandate();

        $actionService->shouldReceive('cancelMandate')
            ->once()
            ->andReturn([
                'success' => false,
                'response_code' => 500,
                'provider_response_code' => 1001,
                'provider_responded' => true,
                'remarks' => 'Cancellation rejected by bank.',
            ]);

        $callbackService->shouldNotReceive('dispatchMandateStatus');

        $result = $service->cancel($debitOrder, 9, 'Customer request.');

        $this->assertFalse($result['success']);
        $this->assertTrue($result['provider_responded']);
        $this->assertSame(DebitOrder::LIFECYCLE_FAILED, $debitOrder->lifecycle_status);
        $this->assertSame(0, $debitOrder->cancelled);
        $this->assertSame('Cancellation rejected by bank.', $debitOrder->remarks);
    }

    public function test_already_cancelled_mandate_stops_before_bank_request(): void
    {
        [$service, $actionService] = $this->workflow();
        $debitOrder = $this->directCancellationMandate();
        $debitOrder->cancelled = 1;
        $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLED;

        $actionService->shouldNotReceive('cancelMandate');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Mandate is already cancelled.');

        $service->cancel($debitOrder, 9, 'Duplicate request.');
    }

    private function workflow(): array
    {
        $actionService = Mockery::mock(MandateActionService::class);
        $callbackService = Mockery::mock(ExternalCallbackService::class);

        $smsService = Mockery::mock(CustomerSmsNotificationService::class);
        $smsService->shouldIgnoreMissing();

        $service = new PaymentMandateWorkflowService(
            Mockery::mock(DeductionService::class),
            Mockery::mock(BankPayloadService::class),
            Mockery::mock(BankApiService::class),
            $actionService,
            $callbackService,
            $smsService
        );

        return [$service, $actionService, $callbackService];
    }

    private function directCancellationMandate(): DebitOrder
    {
        $debitOrder = Mockery::mock(DebitOrder::class)->makePartial();
        $debitOrder->id = 101;
        $debitOrder->reference = 'MANDATE-101';
        $debitOrder->channel = 'CRDB';
        $debitOrder->cancelled = 0;
        $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_ACTIVE;
        $debitOrder->remarks = 'Mandate active.';
        $debitOrder->shouldReceive('save')->andReturnTrue();
        $debitOrder->shouldReceive('refresh')->andReturnSelf();

        return $debitOrder;
    }

    private function cancellationRequest(): array
    {
        $debitOrder = Mockery::mock(DebitOrder::class)->makePartial();
        $debitOrder->id = 100;
        $debitOrder->reference = 'MANDATE-100';
        $debitOrder->channel = 'CRDB';
        $debitOrder->cancelled = 0;
        $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED;
        $debitOrder->shouldReceive('save')->andReturnTrue();
        $debitOrder->shouldReceive('refresh')->andReturnSelf();

        $request = Mockery::mock(DebitOrderRequest::class)->makePartial();
        $request->requested_by = 5;
        $request->requested_at = now()->subDay();
        $request->setRelation('debitOrder', $debitOrder);
        $request->shouldReceive('save')->andReturnTrue();

        return [$request, $debitOrder];
    }
}
