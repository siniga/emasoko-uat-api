<?php

namespace Tests\Unit;

use App\Models\Partner;
use PHPUnit\Framework\TestCase;

class PartnerRegistrationSourceTest extends TestCase
{
    public function test_registration_sources_have_stable_backend_labels(): void
    {
        $this->assertSame([
            Partner::REGISTRATION_SOURCE_SELF_MOBILE => 'Self Registration (Mobile)',
            Partner::REGISTRATION_SOURCE_SELF_WEB => 'Self Registration (Web)',
            Partner::REGISTRATION_SOURCE_OFFICER => 'Officer',
        ], Partner::registrationSourceOptions());
    }
}
