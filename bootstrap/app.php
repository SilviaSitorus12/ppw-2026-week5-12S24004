<?php

use App\Support\ProblemDetails;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Pemanggil SOAP adalah mesin (Sistem Yudisium), bukan form Blade.
        // Form Blade Minggu 6 tetap dilindungi CSRF.
        $middleware->validateCsrfTokens(except: [
            'soap/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 0. Semua rute /api/* SELALU dirender sebagai JSON, walau klien lupa header Accept.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // Urutan penting: handler spesifik dulu, handler umum paling akhir.
        // Semua handler mengembalikan null untuk rute non-API, sehingga halaman Blade tidak berubah.

        // 1. 422 Unprocessable Content (gagal validasi)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ProblemDetails::make(
                status: 422,
                detail: 'Satu atau lebih atribut input yang dikirim tidak memenuhi aturan validasi.',
                title: 'Unprocessable Content',
                slug: 'validation-failed',
                errors: $e->errors(),
            );
        });

        // 2. 401 Unauthorized (token tidak ada / salah / sudah dicabut)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ProblemDetails::make(
                status: 401,
                detail: 'Akses ditolak. Sertakan Bearer Token yang valid pada header Authorization.',
                title: 'Unauthorized',
                slug: 'unauthenticated',
            );
        });

        // 3. 404 Not Found (URI salah ketik atau model tidak ditemukan)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ProblemDetails::make(
                status: 404,
                detail: 'Alamat URI endpoint atau resource yang diminta tidak ditemukan.',
                title: 'Not Found',
                slug: 'resource-not-found',
            );
        });

        // 4. Galat HTTP lain (403, 405, 429, ...) dengan header bawaannya (mis. Allow, Retry-After)
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $response = ProblemDetails::make(
                status: $e->getStatusCode(),
                detail: $e->getMessage() ?: 'Permintaan tidak dapat diproses.',
            );

            foreach ($e->getHeaders() as $name => $value) {
                $response->headers->set($name, $value);
            }

            return $response;
        });

        // 5. 500 Internal Server Error (fallback terakhir)
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') || $e instanceof HttpResponseException) {
                return null;
            }

            return ProblemDetails::make(
                status: 500,
                detail: config('app.debug')
                    ? $e->getMessage()
                    : 'Terjadi kesalahan internal pada server. Silakan coba beberapa saat lagi.',
                title: 'Internal Server Error',
                slug: 'internal-server-error',
            );
        });
    })->create();