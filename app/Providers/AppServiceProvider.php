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

        $externalApiRoutes = [
                'api/v1/token',
                'api/v1/mandate/summaries',
                'api/v1/mandate/all',
                'api/v1/mandate/{reference}',
                'api/v1/mandate/channels',
                'api/v1/mandate/create',
                'api/v1/mandate/otp/verify',
                'api/v1/mandate/otp/create',
                'api/v1/mandate/status',
                'api/v1/mandate/cancellation',
                'api/v1/collections/all',
                'api/v1/collections/filtering',
                'api/v1/collections/actual',
        ];

        $mobileApiRoutes = [
            'api/mobile/v1/login',
            'api/mobile/v1/mandates/summaries',
            'api/mobile/v1/mandates/all',
            'api/mobile/v1/mandates/{reference}',
            'api/mobile/v1/mandates/channels',
            'api/mobile/v1/mandates/create',
            'api/mobile/v1/mandates/otp/verify',
            'api/mobile/v1/mandates/otp/create',
            'api/mobile/v1/mandates/status',
            'api/mobile/v1/mandates/cancellation',
            'api/mobile/v1/collections/all',
            'api/mobile/v1/collections/filtering',
            'api/mobile/v1/collections/actual',
        ];

        Scramble::routes(function (Route $route) use ($externalApiRoutes, $mobileApiRoutes) {
            $audience = env('SCRAMBLE_API_AUDIENCE', 'external');

            return match ($audience) {
                'mobile' => in_array($route->uri, $mobileApiRoutes, true),
                'all' => in_array($route->uri, array_merge($externalApiRoutes, $mobileApiRoutes), true),
                default => in_array($route->uri, $externalApiRoutes, true),
            };
        });

        $pathOrder = [
            'api/v1/token' => 0,
            'api/v1/mandate/summaries' => 10,
            'api/v1/mandate/all' => 11,
            'api/v1/mandate/{reference}' => 12,
            'api/v1/mandate/channels' => 13,
            'api/v1/mandate/create' => 14,
            'api/v1/mandate/otp/verify' => 15,
            'api/v1/mandate/otp/create' => 16,
            'api/v1/mandate/status' => 17,
            'api/v1/mandate/cancellation' => 18,
            'api/v1/collections/all' => 20,
            'api/v1/collections/filtering' => 21,
            'api/v1/collections/actual' => 22,
            'api/mobile/v1/login' => 0,
            'api/mobile/v1/mandates/summaries' => 10,
            'api/mobile/v1/mandates/all' => 11,
            'api/mobile/v1/mandates/{reference}' => 12,
            'api/mobile/v1/mandates/channels' => 13,
            'api/mobile/v1/mandates/create' => 14,
            'api/mobile/v1/mandates/otp/verify' => 15,
            'api/mobile/v1/mandates/otp/create' => 16,
            'api/mobile/v1/mandates/status' => 17,
            'api/mobile/v1/mandates/cancellation' => 18,
            'api/mobile/v1/collections/all' => 20,
            'api/mobile/v1/collections/filtering' => 21,
            'api/mobile/v1/collections/actual' => 22,
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
