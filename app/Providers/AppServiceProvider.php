<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Pagination\Paginator;

use Illuminate\Support\ServiceProvider;

use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Path;

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
        app(AuditTrailService::class)->register($this);

        Scramble::routes(function (Route $route) {
            return in_array($route->uri, [
                'api/v1/login',
                'api/v1/token',
                'api/v1/mandate/summaries',
                'api/v1/mandate/all',
                'api/v1/mandate/{reference}',
                'api/v1/mandate/create',
                'api/v1/mandate/otp/create',
                'api/v1/mandate/otp/verify',
                'api/v1/mandate/status',
                'api/v1/mandate/cancellation',
                'api/v1/collections/all',
                'api/v1/collections/filtering',
                'api/v1/collections/actual',
            ], true);
        });

        Scramble::extendOpenApi(function (OpenApi $openApi) {
            $groupOrder = [
                'Auth' => 0,
                'PaymentMandate' => 1,
                'PaymentCollection' => 2,
            ];

            usort($openApi->paths, function (Path $a, Path $b) use ($groupOrder) {
                $aOperation = reset($a->operations) ?: null;
                $bOperation = reset($b->operations) ?: null;

                $aTag = $aOperation->tags[0] ?? '';
                $bTag = $bOperation->tags[0] ?? '';

                $aRank = $groupOrder[$aTag] ?? 99;
                $bRank = $groupOrder[$bTag] ?? 99;

                if ($aRank !== $bRank) {
                    return $aRank <=> $bRank;
                }

                return strcmp($a->path, $b->path);
            });
        });

        // Define a gate for viewing API docs
        // This allows only specific users to view the API documentation
        Gate::define('viewApiDocs', function (User $user) {
            return in_array($user->email, ['admin@solocodes.co.tz']);
        });
    }
}
