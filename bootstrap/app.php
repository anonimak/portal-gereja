<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Preview ngrok/proxy: percayai header X-Forwarded-* (nginx di depan
        // php-fpm) supaya scheme https & host mengikuti request asli,
        // tanpa hardcode domain ngrok (URL free-tier bisa berubah).
        $middleware->trustProxies(
            at: "*",
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Jangan menulis log untuk error yang "normal" (validasi & 404) supaya
        // log terstruktur tidak penuh noise.
        $exceptions->dontReport([
            ValidationException::class,
            NotFoundHttpException::class,
        ]);

        // Logging terstruktur: lampirkan konteks request (user, gereja, URL)
        // ke setiap exception yang di-report. Default reporting tetap berjalan
        // (closure ini tidak mengembalikan false).
        $exceptions->report(function (Throwable $e): void {
            if (auth()->check()) {
                Log::withContext([
                    'user_id' => auth()->id(),
                    'church_id' => auth()->user()?->church_id,
                    'url' => request()?->fullUrl(),
                    'method' => request()?->method(),
                ]);
            }
        });

        // Pastikan error 4xx/5xx memakai halaman error kustom yang ramah
        // (tidak pernah membocorkan stack trace / detail exception ke user).
        // Hanya HTTP exceptions yang ditangani di sini; AuthenticationException
        // (redirect ke login) dan exception lain dibiarkan ke default handler.
        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || ! $e instanceof HttpExceptionInterface) {
                return null;
            }

            $status = $e->getStatusCode();

            if (in_array($status, [403, 404, 500, 503], true) && view()->exists("errors.{$status}")) {
                return response()->view("errors.{$status}", ['exception' => $e], $status);
            }

            return null; // default Laravel handling
        });
    })->create();
