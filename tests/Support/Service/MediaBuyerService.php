<?php

declare(strict_types=1);

namespace Tests\Support\Service;

use Tests\Support\ApiTester;
use Tests\Support\Constants\Endpoints;
use Tests\Support\Models\Request\MediaBuyerRequest;

/**
 * Service layer over the Media Buyers HTTP endpoints.
 *
 * This is the ONLY place that knows how a Media Buyer request is turned into
 * an HTTP call. Tests never call $I->sendGet/sendPost directly for this
 * resource — they go through the service. Benefits as the suite grows:
 *
 *  - The HTTP verb + path for each operation lives in one method.
 *  - If headers, auth, or the request envelope change, one edit fixes every test.
 *  - Tests read at the level of the domain ("create this buyer") not transport.
 *
 * The service is intentionally thin: it sends the request and leaves all
 * assertions to the Step/Cest layer, so it stays reusable for both positive
 * and negative paths.
 */
final class MediaBuyerService
{
    public function __construct(private readonly ApiTester $I)
    {
    }

    /** GET /api/mediabuyers — list all media buyers. */
    public function list(): void
    {
        $this->I->sendGet(Endpoints::MEDIA_BUYERS);
    }

    /** POST /api/mediabuyers — create a media buyer from a request model. */
    public function create(MediaBuyerRequest $request): void
    {
        $this->I->sendPost(Endpoints::MEDIA_BUYERS, $request->toArray());
    }

    /**
     * POST with a raw array body. Used by negative tests that need to send
     * malformed payloads the typed model would not naturally express.
     *
     * @param array<string, mixed> $body
     */
    public function createRaw(array $body): void
    {
        $this->I->sendPost(Endpoints::MEDIA_BUYERS, $body);
    }
}
