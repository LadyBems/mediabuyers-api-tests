<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Inherited methods.
 *
 * In a generated Codeception project this file is produced by
 * `codecept build` from the enabled modules. It is committed here so the
 * suite is readable without a build step (there is no live environment for
 * this task).
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * REST module:
 * @method void haveHttpHeader($name, $value)
 * @method void seeHttpHeader($name, $value = null)
 * @method void sendGet($url, $params = [])
 * @method void sendPost($url, $params = [], $files = [])
 * @method void seeResponseCodeIs($code)
 * @method void seeResponseIsJson()
 * @method void seeResponseEquals($expected)
 * @method void seeResponseContainsJson($json = [])
 * @method void seeResponseJsonMatchesJsonPath($jsonPath)
 * @method mixed grabDataFromResponseByJsonPath($jsonPath)
 * @method string grabResponse()
 *
 * Asserts module:
 * @method void assertSame($expected, $actual, $message = '')
 * @method void assertEquals($expected, $actual, $message = '')
 * @method void assertNotEmpty($actual, $message = '')
 * @method void assertIsArray($actual, $message = '')
 * @method void assertIsInt($actual, $message = '')
 * @method void assertGreaterThan($expected, $actual, $message = '')
 * @method void assertCount($expectedCount, $haystack, $message = '')
 * @method void assertTrue($condition, $message = '')
 * @method void assertContains($needle, $haystack, $message = '')
 *
 * JsonSchema helper:
 * @method void seeResponseMatchesJsonSchema($schemaPath)
 *
 * @SuppressWarnings(PHPMD)
 */
class ApiTester extends \Codeception\Actor
{
    use _generated\ApiTesterActions;
}
