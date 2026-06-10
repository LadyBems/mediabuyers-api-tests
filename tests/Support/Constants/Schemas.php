<?php

declare(strict_types=1);

namespace Tests\Support\Constants;

/**
 * Absolute paths to the JSON Schema files used for response validation.
 *
 * Centralising these means a schema rename or relocation is a one-line change,
 * and tests read declaratively: $I->seeResponseMatchesJsonSchema(Schemas::GET_MEDIA_BUYERS).
 */
final class Schemas
{
    private const SCHEMA_DIR = __DIR__ . '/../../schemas/';

    public const GET_MEDIA_BUYERS = self::SCHEMA_DIR . 'get-media-buyers-schema.json';
    public const POST_MEDIA_BUYER = self::SCHEMA_DIR . 'post-media-buyer-schema.json';
}
