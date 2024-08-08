<?php

namespace App\Providers;

use App\Events\CorporateStatus;
use App\Events\CreateOtp;
use App\Events\VerifyOtp;
use App\Listeners\FetchCorporateStatus;
use App\Listeners\OtpVerificationRequest;
use App\Listeners\SendCreatedOtp;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        CorporateStatus::class => [
            FetchCorporateStatus::class,
        ],

        CreateOtp::class => [
            SendCreatedOtp::class,
        ],

        VerifyOtp::class => [
            OtpVerificationRequest::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
