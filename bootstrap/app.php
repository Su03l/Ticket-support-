<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Services\ErrorLogService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception): void {
            app(ErrorLogService::class)->record($exception, request());
        });

        $exceptions->render(function (Throwable $exception, Request $request): ?Response {
            if (! app()->isProduction()) {
                return null;
            }

            $errorId = $request->attributes->get('error_id');

            if ($errorId === null) {
                return null;
            }

            return response()->view('errors.generic', ['errorId' => $errorId], 500);
        });
    })->create();
