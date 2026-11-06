<?php

namespace Tests\Unit;

use App\Services\EquityCheckoutService;
use CyberSource\ObjectSerializer;
use Tests\TestCase;
use UnexpectedValueException;

class EquityCheckoutSaleTest extends TestCase
{
    private function checkout(): array
    {
        return ['reference' => 'EQUITY-test', 'amount' => '1000.00', 'currency' => 'TZS'];
    }

    private function transaction(string $status): array
    {
        config(['services.cybersource.merchant_id' => 'test-merchant']);
        return ['id' => '123', 'merchantId' => 'test-merchant',
            'clientReferenceInformation' => ['code' => 'EQUITY-test'],
            'orderInformation' => ['amountDetails' => ['totalAmount' => '1000.00', 'currency' => 'TZS']],
            'applicationInformation' => ['status' => $status]];
    }

    public function test_sdk_request_enables_sale_and_payer_authentication_and_preserves_reference(): void
    {
        $request = (new EquityCheckoutService())->captureContextRequest('1000.00', 'TZS', 'EQUITY-test');
        $data = json_decode(json_encode(ObjectSerializer::sanitizeForSerialization($request)), true);
        $this->assertSame('0.28', $data['clientVersion']);
        $this->assertSame('CAPTURE', $data['completeMandate']['type']);
        $this->assertTrue($data['completeMandate']['consumerAuthentication']);
        $this->assertSame('EQUITY-test', $data['data']['clientReferenceInformation']['code']);
        $this->assertSame('1000.00', $data['data']['orderInformation']['amountDetails']['totalAmount']);
        $this->assertSame('TZS', $data['data']['orderInformation']['amountDetails']['currency']);
        // Regression: both request forms together cause UNIFIEDPAYMENTS_VALIDATION_FIELDS.
        $this->assertArrayNotHasKey('orderInformation', $data);
    }

    public function test_only_verified_settlement_marks_payment_paid(): void
    {
        $service = new EquityCheckoutService();
        foreach (['AUTHORIZED', 'PENDING', 'TRANSMITTED', 'DECLINED', 'AUTHORIZED_PENDING_REVIEW', 'REVERSED', 'UNKNOWN'] as $status) {
            $result = $service->verifiedResult('123', $this->transaction($status), $this->checkout());
            $this->assertFalse($result['paid'], $status);
            $this->assertTrue($result['verified']);
        }
        $this->assertTrue($service->verifiedResult('123', $this->transaction('SETTLED'), $this->checkout())['paid']);
    }

    public function test_mismatched_amount_currency_merchant_reference_or_id_is_never_accepted(): void
    {
        foreach (['id', 'merchant', 'reference', 'currency', 'amount'] as $field) {
            $data = $this->transaction('SETTLED');
            match ($field) {
                'id' => $data['id'] = '456',
                'merchant' => $data['merchantId'] = 'other',
                'reference' => $data['clientReferenceInformation']['code'] = 'other',
                'currency' => $data['orderInformation']['amountDetails']['currency'] = 'USD',
                'amount' => $data['orderInformation']['amountDetails']['totalAmount'] = '0.01',
            };
            try {
                (new EquityCheckoutService())->verifiedResult('123', $data, $this->checkout());
                $this->fail('Accepted mismatched '.$field);
            } catch (UnexpectedValueException $exception) {
                $this->assertSame('Transaction does not match checkout.', $exception->getMessage());
            }
        }
    }
}
