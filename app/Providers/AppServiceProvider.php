<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Str;
use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Paginator::useBootstrap();

        Scramble::routes(function (Route $route) {
            return Str::startsWith($route->uri, 'api/v1/');
        });

        // Define a gate for viewing API docs
        // This allows only specific users to view the API documentation
        Gate::define('viewApiDocs', function (User $user) {
            return in_array($user->email, ['admin@solocodes.co.tz']);
        });
    }
}
