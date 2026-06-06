<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Pagination\Paginator;

use Illuminate\Support\ServiceProvider;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Configuration\OperationTransformers;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Path;
use Dedoc\Scramble\Support\RouteInfo;

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
                'api/v1/token',
                'api/v1/mandate/summaries',
                'api/v1/mandate/all',
                'api/v1/mandate/{reference}',
                'api/mandate/channels',
                'api/v1/mandate/create',
                'api/v1/mandate/otp/verify',
                'api/v1/mandate/otp/create',
                'api/v1/mandate/status',
                'api/v1/mandate/cancellation',
                'api/v1/collections/all',
                'api/v1/collections/filtering',
                'api/v1/collections/actual',
            ], true);
        });

        $pathOrder = [
            'api/v1/token' => 0,
            'api/v1/mandate/summaries' => 10,
            'api/v1/mandate/all' => 11,
            'api/v1/mandate/{reference}' => 12,
            'api/mandate/channels' => 13,
            'api/v1/mandate/create' => 14,
            'api/v1/mandate/otp/verify' => 15,
            'api/v1/mandate/otp/create' => 16,
            'api/v1/mandate/status' => 17,
            'api/v1/mandate/cancellation' => 18,
            'api/v1/collections/all' => 20,
            'api/v1/collections/filtering' => 21,
            'api/v1/collections/actual' => 22,
        ];

        Scramble::configure()->withOperationTransformers(function (OperationTransformers $transformers) use ($pathOrder) {
            $transformers->append(function (Operation $operation, RouteInfo $routeInfo) use ($pathOrder): void {
                if (isset($pathOrder[$routeInfo->route->uri])) {
                    $operation->setAttribute('weight', $pathOrder[$routeInfo->route->uri]);
                }
            });
        });

        Scramble::extendOpenApi(function (OpenApi $openApi) {
            $groupOrder = [
                'Auth' => 1,
                'Payment Mandates' => 2,
                'Payment Collections' => 3,
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

                $aWeight = $aOperation?->getAttribute('weight', 999) ?? 999;
                $bWeight = $bOperation?->getAttribute('weight', 999) ?? 999;

                if ($aWeight !== $bWeight) {
                    return $aWeight <=> $bWeight;
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
