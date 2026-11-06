<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Pembentuk respons galat standar IETF RFC 7807 (Problem Details for HTTP APIs).
 * Dipakai bersama oleh trait ApiResponse (controller) dan handler global (bootstrap/app.php),
 * sehingga format galat dari mana pun selalu identik.
 */
final class ProblemDetails
{
    public const CONTENT_TYPE = 'application/problem+json';

    public static function make(
        int $status,
        string $detail,
        ?string $title = null,
        ?string $slug = null,
        array $errors = [],
        ?string $instance = null,
    ): JsonResponse {
        $title ??= SymfonyResponse::$statusTexts[$status] ?? 'Error';
        $slug  ??= Str::slug($title);

        $problem = [
            'type'     => url("/docs/errors/{$slug}"),
            'title'    => $title,
            'status'   => $status,
            'detail'   => $detail,
            'instance' => $instance ?? request()->getRequestUri(),
        ];

        if ($errors !== []) {
            $problem['errors'] = $errors;
        }

        return new JsonResponse(
            $problem,
            $status,
            ['Content-Type' => self::CONTENT_TYPE],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}