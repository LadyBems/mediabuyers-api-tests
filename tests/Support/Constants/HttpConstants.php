<?php

declare(strict_types=1);

namespace Tests\Support\Constants;

/**
 * HTTP-level constants used across the suite: status codes, header names and
 * header values for content negotiation.
 *
 * Keeping these here (rather than as magic numbers/strings in tests) makes the
 * intent of each assertion explicit and gives one edit point if conventions
 * change.
 */
final class HttpConstants
{
    // --- Status codes ---
    public const HTTP_OK = 200;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_CONFLICT = 409;
    public const HTTP_NOT_FOUND = 404;

    // --- Header names ---
    public const HEADER_CONTENT_TYPE = 'Content-Type';
    public const HEADER_ACCEPT = 'Accept';

    // --- Header values ---
    public const CONTENT_TYPE_JSON = 'application/json';
}
