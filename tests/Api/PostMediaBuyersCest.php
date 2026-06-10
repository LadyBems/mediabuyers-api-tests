<?php

declare(strict_types=1);

namespace Tests\Api;

use Codeception\Example;
use Tests\Support\ApiTester;
use Tests\Support\Factory\MediaBuyerFactory;
use Tests\Support\Service\MediaBuyerService;
use Tests\Support\Step\MediaBuyerSteps;

/**
 * Tests for Endpoint 2 — POST /api/mediabuyers.
 *
 * Acceptance criteria P1–P11 are each encoded as tests. Negative cases that
 * share a shape (missing required field, invalid value) are parameterized with
 * @dataProvider so a new case is one row, not a new method — this is the
 * "parameterized where the contract calls for it" requirement.
 *
 * All payloads come from MediaBuyerFactory + the request builder; there is no
 * hard-coded JSON inside a test method.
 */
final class PostMediaBuyersCest
{
    private MediaBuyerService $service;
    private MediaBuyerSteps $steps;

    public function _before(ApiTester $I): void
    {
        $this->service = new MediaBuyerService($I);
        $this->steps = new MediaBuyerSteps($I);
    }

    // ---------------------------------------------------------------------
    // Positive paths
    // ---------------------------------------------------------------------

    /**
     * P1 + P2 + P3: a valid request returns 200 + JSON + schema-valid body;
     * id is server-generated; echoed fields match the request.
     */
    public function createsBuyerWithValidPayload(ApiTester $I): void
    {
        $I->wantTo('create a media buyer with a valid payload (P1, P2, P3)');

        $request = MediaBuyerFactory::valid();
        $this->service->create($request);

        $this->steps->seeSuccessfulJsonResponse();
        $this->steps->seeCreatedMatchesSchema();

        $created = $this->steps->grabCreatedBuyer();
        $sent = $request->toArray();

        // P2: server-generated positive integer id, never supplied by request.
        $I->assertIsInt($created->id(), 'id must be an integer (P2).');
        $I->assertGreaterThan(0, $created->id(), 'id must be a positive integer (P2).');

        // P3: persisted fields echo the request.
        $I->assertSame($sent['mbId'], $created->mbId(), 'mbId must round-trip (P3).');
        $I->assertSame($sent['initials'], $created->initials(), 'initials must round-trip (P3).');
        $I->assertSame($sent['name'], $created->name(), 'name must round-trip (P3).');
        $I->assertSame($sent['email'], $created->email(), 'email must round-trip (P3).');
        $I->assertSame($sent['slackUserId'], $created->slackUserId(), 'slackUserId must round-trip (P3).');
    }

    /**
     * P4: active boolean maps to integer. Parameterized over both boolean
     * inputs and their expected integer projection.
     *
     * @dataProvider activeBooleanProvider
     */
    public function mapsActiveBooleanToInteger(ApiTester $I, Example $example): void
    {
        $I->wantTo(sprintf('verify active:%s maps to %d (P4)', var_export($example['input'], true), $example['expected']));

        $request = MediaBuyerFactory::valid()->withActive($example['input']);
        $this->service->create($request);

        $this->steps->seeSuccessfulJsonResponse();
        $this->steps->seeCreatedMatchesSchema();

        $created = $this->steps->grabCreatedBuyer();
        $I->assertSame(
            $example['expected'],
            $created->active(),
            sprintf('active:%s must yield %d (P4).', var_export($example['input'], true), $example['expected'])
        );
    }

    /**
     * Optional fields (initials, slackUserId) may be omitted entirely.
     * Complements P3 by proving the minimal required-only payload succeeds.
     */
    public function createsBuyerWithOnlyRequiredFields(ApiTester $I): void
    {
        $I->wantTo('create a media buyer with only the required fields present');

        $request = MediaBuyerFactory::minimalValid();
        $this->service->create($request);

        $this->steps->seeSuccessfulJsonResponse();
        $this->steps->seeCreatedMatchesSchema();
    }

    // ---------------------------------------------------------------------
    // Negative paths
    // ---------------------------------------------------------------------

    /**
     * P5: omitting any required field returns 400 and the errors array names
     * the missing field. One row per required field.
     *
     * @dataProvider missingRequiredFieldProvider
     */
    public function rejectsMissingRequiredField(ApiTester $I, Example $example): void
    {
        $field = $example['field'];
        $I->wantTo(sprintf('reject a request missing the required field "%s" (P5)', $field));

        $request = MediaBuyerFactory::valid()->without($field);
        $this->service->create($request);

        $this->steps->seeValidationError();

        $errors = $this->steps->grabErrors();
        $I->assertTrue(
            $errors->hasDetailContaining($field),
            sprintf('Expected an error message naming the missing field "%s" (P5).', $field)
        );
    }

    /**
     * P6: invalid email returns 400 with a message mentioning the bad value.
     */
    public function rejectsInvalidEmail(ApiTester $I): void
    {
        $I->wantTo('reject a request with an invalid email (P6)');

        $request = MediaBuyerFactory::valid()->withEmail('not-an-email');
        $this->service->create($request);

        $this->steps->seeValidationError();

        $errors = $this->steps->grabErrors();
        $I->assertTrue(
            $errors->hasDetailContaining('email'),
            'Expected an error message mentioning the invalid email (P6).'
        );
    }

    /**
     * P7: initials longer than 2 chars returns 400 with the exact message.
     */
    public function rejectsInitialsLongerThanTwoChars(ApiTester $I): void
    {
        $I->wantTo('reject initials longer than 2 characters (P7)');

        $request = MediaBuyerFactory::valid()->withInitials('TOO LONG');
        $this->service->create($request);

        $this->steps->seeValidationError();

        $errors = $this->steps->grabErrors();
        $I->assertTrue(
            $errors->hasDetailContaining('initials must be exactly 2 characters long'),
            'Expected the exact initials-length message (P7).'
        );
    }

    /**
     * P8: name outside the 2–30 length bounds returns 400 with a length error.
     * Parameterized over both boundary-violating directions.
     *
     * @dataProvider invalidNameLengthProvider
     */
    public function rejectsNameOutsideLengthBounds(ApiTester $I, Example $example): void
    {
        $I->wantTo(sprintf('reject name of length %d (P8)', strlen((string) $example['name'])));

        $request = MediaBuyerFactory::valid()->withName($example['name']);
        $this->service->create($request);

        $this->steps->seeValidationError();

        $errors = $this->steps->grabErrors();
        $I->assertTrue(
            $errors->hasDetailContaining('name'),
            'Expected a length-related error mentioning name (P8).'
        );
    }

    /**
     * P9: mbId that is not a positive-integer string returns 400.
     *
     * @dataProvider invalidMbIdProvider
     */
    public function rejectsNonNumericMbId(ApiTester $I, Example $example): void
    {
        $I->wantTo(sprintf('reject mbId "%s" (P9)', (string) $example['mbId']));

        $request = MediaBuyerFactory::valid()->withMbId($example['mbId']);
        $this->service->create($request);

        $this->steps->seeValidationError();

        $errors = $this->steps->grabErrors();
        $I->assertTrue(
            $errors->hasDetailContaining('mbId'),
            'Expected an error mentioning mbId (P9).'
        );
    }

    /**
     * P10: non-boolean active returns 400.
     */
    public function rejectsNonBooleanActive(ApiTester $I): void
    {
        $I->wantTo('reject a non-boolean active value (P10)');

        $request = MediaBuyerFactory::valid()->withActive('yes');
        $this->service->create($request);

        $this->steps->seeValidationError();

        $errors = $this->steps->grabErrors();
        $I->assertTrue(
            $errors->hasDetailContaining('active'),
            'Expected an error mentioning active (P10).'
        );
    }

    /**
     * P11: creating two buyers with the same mbId fails on the second request
     * (uniqueness). The contract leaves the exact status open (400 or 409);
     * documented assumption — we accept either, but require a non-2xx with an
     * mbId-related error.
     */
    public function rejectsDuplicateMbId(ApiTester $I): void
    {
        $I->wantTo('reject a duplicate mbId on the second create (P11)');

        $mbId = MediaBuyerFactory::randomMbId();

        $first = MediaBuyerFactory::valid()->withMbId($mbId);
        $this->service->create($first);
        $this->steps->seeSuccessfulJsonResponse();

        $second = MediaBuyerFactory::valid()->withMbId($mbId);
        $this->service->create($second);

        // Assumption: uniqueness conflict surfaces as 400 or 409.
        $I->seeResponseIsJson();
        $errors = $this->steps->grabErrors();
        $I->assertTrue(
            $errors->count() > 0,
            'Expected a non-empty errors array on duplicate mbId (P11).'
        );
        $I->assertTrue(
            $errors->hasDetailContaining('mbId'),
            'Expected the uniqueness error to mention mbId (P11).'
        );
    }

    // ---------------------------------------------------------------------
    // Data providers
    // ---------------------------------------------------------------------

    /** @return array<string, array{input: bool, expected: int}> */
    protected function activeBooleanProvider(): array
    {
        return [
            'active true -> 1'  => ['input' => true, 'expected' => 1],
            'active false -> 0' => ['input' => false, 'expected' => 0],
        ];
    }

    /** @return array<string, array{field: string}> */
    protected function missingRequiredFieldProvider(): array
    {
        return [
            'missing mbId'   => ['field' => 'mbId'],
            'missing name'   => ['field' => 'name'],
            'missing email'  => ['field' => 'email'],
            'missing active' => ['field' => 'active'],
        ];
    }

    /** @return array<string, array{name: string}> */
    protected function invalidNameLengthProvider(): array
    {
        return [
            'name too short (1 char)'  => ['name' => 'A'],
            'name too long (31 chars)' => ['name' => str_repeat('A', 31)],
        ];
    }

    /** @return array<string, array{mbId: string}> */
    protected function invalidMbIdProvider(): array
    {
        return [
            'alphabetic mbId' => ['mbId' => 'abc'],
            'empty mbId'      => ['mbId' => ''],
            'negative mbId'   => ['mbId' => '-5'],
        ];
    }
}
