<?php

declare(strict_types=1);

namespace Tests\Support\Config;

/**
 * Single source of truth for environment-dependent configuration.
 *
 * Values are read from environment variables (loaded from .env by the
 * bootstrap, or injected by CI). Tests and the service layer ask this class
 * for configuration instead of touching $_ENV directly, so the resolution
 * strategy can change in one place.
 */
final class EnvironmentConfig
{
    /**
     * Base URL of the target environment, without the /api suffix.
     * Falls back to a placeholder so the suite is loadable with no .env present.
     */
    public static function baseUrl(): string
    {
        return self::get('BASE_URL', 'https://staging.example.com');
    }

    /** Full API root, e.g. https://staging.example.com/api */
    public static function apiUrl(): string
    {
        return rtrim(self::baseUrl(), '/') . '/api';
    }

    private static function get(string $key, ?string $default = null): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            if ($default === null) {
                throw new \RuntimeException("Missing required environment variable: {$key}");
            }
            return $default;
        }

        return (string) $value;
    }
}
