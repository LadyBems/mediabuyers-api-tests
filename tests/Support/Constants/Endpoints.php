<?php

declare(strict_types=1);

namespace Tests\Support\Constants;

/**
 * Centralised endpoint paths for the Media Buyers resource.
 *
 * Tests and the service layer reference these constants instead of repeating
 * string literals. When the contract moves an endpoint, there is exactly one
 * place to change it — the "spec change -> test change" path stays short.
 *
 * Paths are relative to the REST module base URL (%BASE_URL%/api), so they do
 * not include the host or the /api prefix.
 */
final class Endpoints
{
    /** Collection endpoint: GET (list) and POST (create). */
    public const MEDIA_BUYERS = '/mediabuyers';

    /** Single-resource endpoint, for future GET/PUT/DELETE by id. */
    public static function mediaBuyer(int $id): string
    {
        return self::MEDIA_BUYERS . '/' . $id;
    }
}
