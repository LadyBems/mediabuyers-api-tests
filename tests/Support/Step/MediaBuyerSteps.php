<?php

declare(strict_types=1);

namespace Tests\Support\Step;

use Tests\Support\ApiTester;
use Tests\Support\Constants\HttpConstants;
use Tests\Support\Constants\Schemas;
use Tests\Support\Models\Response\ErrorResponse;
use Tests\Support\Models\Response\MediaBuyerResponse;

/**
 * High-level, reusable steps that combine sending a request with the common
 * assertions the contract demands. Cest classes compose these instead of
 * repeating the same five assertions in every method.
 *
 * Division of labour:
 *   Service  -> "how do I send it" (HTTP verb + path)
 *   Steps    -> "what does a correct response look like" (status, headers,
 *               schema, envelope shape)
 *   Cest     -> "which scenario am I exercising" (the acceptance criterion)
 *
 * When the contract tightens a rule, the assertion changes here once and every
 * test inherits it.
 */
final class MediaBuyerSteps
{
    public function __construct(private readonly ApiTester $I)
    {
    }

    /** Assert the standard success envelope for a 200 JSON response. */
    public function seeSuccessfulJsonResponse(): void
    {
        $this->I->seeResponseCodeIs(HttpConstants::HTTP_OK);
        $this->I->seeHttpHeader(
            HttpConstants::HEADER_CONTENT_TYPE,
            HttpConstants::CONTENT_TYPE_JSON
        );
        $this->I->seeResponseIsJson();
    }

    /** Assert a 400 validation error envelope with a non-empty errors array. */
    public function seeValidationError(): void
    {
        $this->I->seeResponseCodeIs(HttpConstants::HTTP_BAD_REQUEST);
        $this->I->seeResponseIsJson();
        $this->I->seeResponseJsonMatchesJsonPath('$.errors');
    }

    public function seeListMatchesSchema(): void
    {
        $this->I->seeResponseMatchesJsonSchema(Schemas::GET_MEDIA_BUYERS);
    }

    public function seeCreatedMatchesSchema(): void
    {
        $this->I->seeResponseMatchesJsonSchema(Schemas::POST_MEDIA_BUYER);
    }

    /** @return array<int, array<string, mixed>> the decoded data array (GET list) */
    public function grabList(): array
    {
        return (array) $this->I->grabDataFromResponseByJsonPath('$.data')[0];
    }

    /** Decode the single created buyer (POST) into a typed model. */
    public function grabCreatedBuyer(): MediaBuyerResponse
    {
        $data = (array) $this->I->grabDataFromResponseByJsonPath('$.data')[0];
        return MediaBuyerResponse::fromArray($data);
    }

    /** Decode a 400 body into a typed error model. */
    public function grabErrors(): ErrorResponse
    {
        $body = json_decode($this->I->grabResponse(), true) ?: [];
        return ErrorResponse::fromBody($body);
    }
}
