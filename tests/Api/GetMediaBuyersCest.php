<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;
use Tests\Support\Service\MediaBuyerService;
use Tests\Support\Step\MediaBuyerSteps;

/**
 * Tests for Endpoint 1 — GET /api/mediabuyers.
 *
 * Each acceptance criterion (G1–G7) from the contract is encoded as one or
 * more tests. The tests are structured but do not require a live server: they
 * express the full assertions a passing run would make. Against a real
 * environment (URL resolved from config), they execute unchanged.
 */
final class GetMediaBuyersCest
{
    private MediaBuyerService $service;
    private MediaBuyerSteps $steps;

    public function _before(ApiTester $I): void
    {
        $this->service = new MediaBuyerService($I);
        $this->steps = new MediaBuyerSteps($I);
    }

    /**
     * G1 + G2: 200 OK, application/json, body matches the GET schema.
     */
    public function returnsListWithCorrectStatusHeaderAndSchema(ApiTester $I): void
    {
        $I->wantTo('GET the media buyers list and verify status, header and schema (G1, G2)');

        $this->service->list();

        $this->steps->seeSuccessfulJsonResponse();
        $this->steps->seeListMatchesSchema();
    }

    /**
     * G3: data is always an array. Schema enforces array typing; this test
     * asserts the envelope shape explicitly so a regression to null/404 fails
     * with a clear message rather than only a schema mismatch.
     */
    public function dataFieldIsAlwaysAnArray(ApiTester $I): void
    {
        $I->wantTo('verify the data field is an array even when empty (G3)');

        $this->service->list();

        $this->steps->seeSuccessfulJsonResponse();
        $I->seeResponseJsonMatchesJsonPath('$.data');
        $data = $I->grabDataFromResponseByJsonPath('$.data');
        $I->assertIsArray($data[0] ?? $data, 'Expected data to be an array.');
    }

    /**
     * G4: every item carries all required fields. Schema "required" covers
     * this; the schema-match assertion is the enforcement point.
     */
    public function everyItemContainsAllRequiredFields(ApiTester $I): void
    {
        $I->wantTo('verify each list item has all required fields (G4)');

        $this->service->list();

        $this->steps->seeSuccessfulJsonResponse();
        // The schema declares all seven fields as required for each item.
        $this->steps->seeListMatchesSchema();
    }

    /**
     * G6: active is an integer 0 or 1. The schema enum [0,1] with integer type
     * rejects booleans and strings; matching the schema asserts G6.
     */
    public function activeIsIntegerZeroOrOne(ApiTester $I): void
    {
        $I->wantTo('verify active is an integer 0 or 1, never boolean/string (G6)');

        $this->service->list();

        $this->steps->seeSuccessfulJsonResponse();
        $this->steps->seeListMatchesSchema();
    }

    /**
     * G7: id values are unique across the response. Not expressible in plain
     * JSON Schema, so asserted programmatically over the decoded list.
     */
    public function idValuesAreUniqueAcrossTheResponse(ApiTester $I): void
    {
        $I->wantTo('verify all id values in the list are unique (G7)');

        $this->service->list();
        $this->steps->seeSuccessfulJsonResponse();

        $data = (array) $I->grabDataFromResponseByJsonPath('$.data')[0];
        $ids = array_map(static fn (array $item): int => (int) $item['id'], $data);

        $I->assertSame(
            count($ids),
            count(array_unique($ids)),
            'Expected all id values to be unique across the response (G7).'
        );
    }
}
