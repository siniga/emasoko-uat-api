<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SelfCompanyRegistrationAccessTest extends TestCase
{
    public function test_public_registration_success_page_does_not_require_login(): void
    {
        $this->get(route('self-registration.success'))
            ->assertOk()
            ->assertSee('Information submitted successfully')
            ->assertSee('get back to you shortly');
    }

    public function test_login_page_links_to_public_service_provider_registration(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Register your company')
            ->assertSee(route('self-registration.information'), false);
    }

    public function test_draft_cannot_be_opened_without_matching_session(): void
    {
        $this->get(route('self-registration.information', Str::uuid()))
            ->assertNotFound();
    }

    public function test_public_registration_does_not_expose_collection_account_routes(): void
    {
        $this->assertFalse(Route::has('self-registration.accounts'));
        $this->assertFalse(Route::has('self-registration.store-accounts'));
        $this->assertTrue(Route::has('self-registration.cancel'));
    }

    public function test_backend_provider_deletion_routes_require_delete_requests(): void
    {
        $this->assertSame(
            ['DELETE'],
            Route::getRoutes()->getByName('companies.delete')->methods()
        );
        $this->assertSame(
            ['DELETE'],
            Route::getRoutes()->getByName('companies.cancel-registration')->methods()
        );
    }
}
