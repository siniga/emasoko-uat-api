<?php

namespace Tests\Unit;

use App\Services\EquityCheckoutService;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

class EquityCheckoutTokenTest extends TestCase
{
    private function signedToken(array $changes = []): array
    {
        // Windows PHP may have no default OpenSSL config. Use an isolated test config.
        $config = tempnam(sys_get_temp_dir(), 'equity-openssl-');
        file_put_contents($config, "[req]\ndistinguished_name = dn\n[dn]\n");
        try {
            $private = openssl_pkey_new(['private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => $config]);
            openssl_pkey_export($private, $pem, null, ['config' => $config]);
        } finally {
            unlink($config);
        }
        $rsa = openssl_pkey_get_details($private)['rsa'];
        $key = ['kty' => 'RSA', 'n' => JWT::urlsafeB64Encode($rsa['n']), 'e' => JWT::urlsafeB64Encode($rsa['e'])];
        $token = JWT::encode(array_merge([
            'iss' => 'Flex/00', 'jti' => 'token-1', 'iat' => time() - 1, 'exp' => time() + 900,
        ], $changes), $pem, 'RS256');

        return [$token, $key];
    }

    public function test_valid_signed_transient_token_is_accepted(): void
    {
        [$token, $key] = $this->signedToken();
        (new EquityCheckoutService())->verifyToken($token, $key);
        $this->addToAssertionCount(1);
    }

    public function test_token_from_another_checkout_key_is_rejected(): void
    {
        [$token] = $this->signedToken();
        [, $otherKey] = $this->signedToken();
        $this->expectException(\Firebase\JWT\SignatureInvalidException::class);
        (new EquityCheckoutService())->verifyToken($token, $otherKey);
    }

    public function test_expired_token_is_rejected(): void
    {
        [$token, $key] = $this->signedToken(['exp' => time() - 1]);
        $this->expectException(\Firebase\JWT\ExpiredException::class);
        (new EquityCheckoutService())->verifyToken($token, $key);
    }
}
