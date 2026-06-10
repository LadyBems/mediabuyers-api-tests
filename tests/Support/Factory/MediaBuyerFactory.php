<?php

declare(strict_types=1);

namespace Tests\Support\Factory;

use Tests\Support\Models\Request\MediaBuyerRequest;

/**
 * Factory for Media Buyer request payloads.
 *
 * Every test starts from a known-good baseline (valid()) and mutates only the
 * field under test. This keeps negative tests honest: a 400 must come from the
 * single field we changed, not from incidental garbage elsewhere in the body.
 *
 * mbId is randomised per build so uniqueness-sensitive tests don't collide
 * when run against a real, stateful environment.
 */
final class MediaBuyerFactory
{
    /** A fully valid request that should pass every POST acceptance criterion. */
    public static function valid(): MediaBuyerRequest
    {
        return (new MediaBuyerRequest())
            ->withMbId(self::randomMbId())
            ->withInitials('TM')
            ->withName('Test Media Buyer')
            ->withEmail('test.media.buyer@example.com')
            ->withSlackUserId('U05AZ3DQBBKK')
            ->withActive(true);
    }

    /**
     * Minimal valid request: only the required fields (mbId, name, email,
     * active). Optional fields (initials, slackUserId) are omitted to prove
     * they really are optional.
     */
    public static function minimalValid(): MediaBuyerRequest
    {
        return (new MediaBuyerRequest())
            ->withMbId(self::randomMbId())
            ->withName('Min Valid')
            ->withEmail('min.valid@example.com')
            ->withActive(false);
    }

    /** Numeric string identifier, as the contract requires (digits only). */
    public static function randomMbId(): string
    {
        return (string) random_int(1000, 99999999);
    }
}
