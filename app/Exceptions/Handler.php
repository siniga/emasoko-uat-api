<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var string[]
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var string[]
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            $loginUrl = route('otp.login');
            $redirectTo = $request->headers->get('referer') ?: $loginUrl;

            if ($request->is('otp/generate', 'otp/login') || $request->routeIs('otp.generate', 'otp.getlogin')) {
                $redirectTo = $loginUrl;
            }

            return redirect()
                ->to($redirectTo)
                ->withInput($request->except($this->dontFlash))
                ->with('error', 'Your session expired. Please try signing in again.');
        });
    }
}
