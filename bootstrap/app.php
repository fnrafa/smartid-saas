<?php

use App\Modules\Document\Exceptions\QuotaExceededException;
use App\Modules\Document\Exceptions\UnauthorizedActionException;
use App\Modules\Tenant\Middleware\EnsureTenantContext;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.context' => EnsureTenantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QuotaExceededException $e, $request) {
            if ($request->is('admin/*') && !$request->expectsJson()) {
                Notification::make()
                    ->danger()
                    ->title('Quota Terlampaui')
                    ->body($e->getMessage())
                    ->persistent()
                    ->send();

                if ($request->is('admin/document-resource/*')) {
                    return redirect('/admin/document-resource/documents');
                }

                return redirect('/admin');
            }

            return $e->render();
        });

        $exceptions->render(function (UnauthorizedActionException $e, $request) {
            return $e->render($request);
        });

        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->is('admin/*') && !$request->expectsJson()) {
                Notification::make()
                    ->danger()
                    ->title('Akses Ditolak')
                    ->body('Anda tidak memiliki izin untuk mengakses resource ini.')
                    ->persistent()
                    ->send();

                if ($request->is('admin/document-resource/*')) {
                    return redirect('/admin/document-resource/documents');
                }

                return redirect('/admin');
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('admin/*') && !$request->expectsJson()) {
                Notification::make()
                    ->danger()
                    ->title('Akses Ditolak')
                    ->body('Anda tidak memiliki izin untuk mengakses halaman ini.')
                    ->persistent()
                    ->send();

                if ($request->is('admin/document-resource/*')) {
                    return redirect('/admin/document-resource/documents');
                }

                return redirect('/admin');
            }
        });

        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 403 && $request->is('admin/*') && !$request->expectsJson()) {
                Notification::make()
                    ->danger()
                    ->title('Akses Ditolak')
                    ->body('Anda tidak memiliki izin untuk mengakses resource ini.')
                    ->persistent()
                    ->send();

                if ($request->is('admin/document-resource/*')) {
                    return redirect('/admin/document-resource/documents');
                }

                return redirect('/admin');
            }
        });

        $exceptions->dontReport([
            UnauthorizedActionException::class,
            QuotaExceededException::class,
        ]);
    })->create();
