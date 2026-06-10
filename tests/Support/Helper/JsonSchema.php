<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Module;
use Codeception\Module\REST;
use JsonSchema\Validator;

/**
 * Codeception helper that validates the current REST response body against a
 * JSON Schema file (draft-07). Exposed to tests as
 * $I->seeResponseMatchesJsonSchema($pathToSchema).
 *
 * Why a helper rather than inline validation in each test:
 *  - Every successful response must be schema-validated (contract requirement).
 *    Centralising it guarantees the validation is identical everywhere.
 *  - The schema lives in a file, versionable alongside the contract; the test
 *    just names which schema applies.
 *  - When we move to schema-versioning or contract testing, this is the single
 *    integration point to extend.
 */
final class JsonSchema extends Module
{
    /**
     * @throws \JsonSchema\Exception\ValidationException via assertion failure
     */
    public function seeResponseMatchesJsonSchema(string $schemaPath): void
    {
        $this->assertFileExists($schemaPath, "JSON Schema not found: {$schemaPath}");

        /** @var REST $rest */
        $rest = $this->getModule('REST');
        $responseBody = $rest->grabResponse();

        $data = json_decode($responseBody);
        $this->assertNotNull(
            $data,
            'Response body is not valid JSON, cannot validate against schema.'
        );

        $schema = json_decode((string) file_get_contents($schemaPath));

        $validator = new Validator();
        $validator->validate($data, $schema);

        if (!$validator->isValid()) {
            $messages = array_map(
                static fn (array $error): string => sprintf('[%s] %s', $error['property'], $error['message']),
                $validator->getErrors()
            );
            $this->fail(
                "Response does not match schema {$schemaPath}:\n - " . implode("\n - ", $messages)
            );
        }

        $this->assertTrue(true, 'Response matches JSON Schema.');
    }
}
