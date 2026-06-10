# Media Buyers API — Automated Test Suite (PHP + Codeception)

Automated API test suite for the **Media Buyers** REST resource, written against
the contract supplied in the task. The contract is treated as the source of
truth; there is no live server, so the focus is on how the suite **abstracts,
organizes, and expresses** tests rather than on a passing run.

> There is no requirement to make the suite runnable. Against a real environment
> (with `BASE_URL` resolved from configuration) the tests execute unchanged — no
> test contains a hard-coded host, payload, or schema.

---

## Codeception setup

For a REST API of this shape I use Codeception 5 with:

| Module | Why |
| --- | --- |
| **REST** | Primary driver: sends requests, inspects status/headers/body, JSON-path grabs. |
| **PhpBrowser** | HTTP transport the REST module depends on. |
| **Asserts** | Framework-level assertions (`assertSame`, `assertCount`, …) inside Cest methods. |
| **JsonSchema** (custom helper) | Validates every successful response against a draft-07 schema file. |

The single API suite is configured in `tests/Api.suite.yml`. The base URL is
injected as `%BASE_URL%/api` and resolved from environment configuration, so
switching environments is a one-variable change and never a code edit.

---

## Repository layout

```
.
├── codeception.yml                     # Global config; loads .env params
├── composer.json                       # PHP deps: codeception, modules, dotenv, json-schema
├── .env / .env.example                 # Per-environment config (BASE_URL)
└── tests
    ├── Api.suite.yml                   # Suite: REST + PhpBrowser + Asserts + JsonSchema
    ├── _bootstrap.php                  # Loads .env at runtime
    ├── Api                             # The tests themselves
    │   ├── GetMediaBuyersCest.php      # GET /mediabuyers  — criteria G1–G7
    │   └── PostMediaBuyersCest.php     # POST /mediabuyers — criteria P1–P11
    ├── schemas                         # JSON Schemas (response contracts)
    │   ├── get-media-buyers-schema.json
    │   └── post-media-buyer-schema.json
    └── Support
        ├── ApiTester.php               # Actor (inherited module methods)
        ├── Config/EnvironmentConfig.php# Single source of env-derived config
        ├── Constants                   # Endpoints, HTTP codes/headers, schema paths
        │   ├── Endpoints.php
        │   ├── HttpConstants.php
        │   └── Schemas.php
        ├── Factory/MediaBuyerFactory.php   # Valid/minimal request builders
        ├── Helper/JsonSchema.php       # seeResponseMatchesJsonSchema()
        ├── Models
        │   ├── Request/MediaBuyerRequest.php    # Fluent request builder
        │   └── Response                          # Typed response views
        │       ├── MediaBuyerResponse.php
        │       └── ErrorResponse.php
        ├── Service/MediaBuyerService.php   # HTTP boundary for the resource
        └── Step/MediaBuyerSteps.php         # Reusable composite assertions
```

### Layered design — separation of concerns

The suite is built in layers so a change at one level does not ripple through
the tests:

- **Constants** — endpoint paths, HTTP codes/headers, schema file paths. One
  edit point each; no magic strings in tests.
- **Config** — resolves `BASE_URL` (and future credentials) from the
  environment. Tests never read `$_ENV` directly.
- **Models (Request/Response)** — typed shapes for payloads and responses.
  `MediaBuyerRequest` is a fluent builder; responses are wrapped so tests use
  `$buyer->active()` instead of array-key access.
- **Factory** — produces a known-good baseline payload. Negative tests start
  from `valid()` and mutate one field, so a 400 is attributable to that field.
- **Service** — the only place that knows the HTTP verb + path for an
  operation. Tests call `service->create($request)`, not `sendPost(...)`.
- **Steps** — composite assertions ("a correct success looks like 200 + JSON +
  schema"). Encoded once, reused everywhere.
- **Cest tests** — express *which* acceptance criterion is exercised, reading
  almost like the contract.

A "spec change → test change" path stays short: e.g. an endpoint rename touches
only `Endpoints`; a response field rename touches only the model + schema.

---

## Scenario selection

Every acceptance criterion in the contract is encoded as at least one test.

**GET /api/mediabuyers (`GetMediaBuyersCest`)**

| Criterion | Test |
| --- | --- |
| G1 status + G2 schema | `returnsListWithCorrectStatusHeaderAndSchema` |
| G3 data always an array | `dataFieldIsAlwaysAnArray` |
| G4 all required fields | `everyItemContainsAllRequiredFields` |
| G6 active is 0/1 integer | `activeIsIntegerZeroOrOne` |
| G7 unique ids | `idValuesAreUniqueAcrossTheResponse` |

G5 (valid emails) is enforced by the schema's `"format": "email"` and is covered
by the schema-match assertions.

**POST /api/mediabuyers (`PostMediaBuyersCest`)**

| Criterion | Test |
| --- | --- |
| P1/P2/P3 valid create, generated id, round-trip | `createsBuyerWithValidPayload` |
| P4 active boolean → integer | `mapsActiveBooleanToInteger` (parameterized) |
| optional-only payload | `createsBuyerWithOnlyRequiredFields` |
| P5 missing required field | `rejectsMissingRequiredField` (parameterized × 4) |
| P6 invalid email | `rejectsInvalidEmail` |
| P7 initials too long | `rejectsInitialsLongerThanTwoChars` |
| P8 name length bounds | `rejectsNameOutsideLengthBounds` (parameterized × 2) |
| P9 non-numeric mbId | `rejectsNonNumericMbId` (parameterized × 3) |
| P10 non-boolean active | `rejectsNonBooleanActive` |
| P11 duplicate mbId | `rejectsDuplicateMbId` |

This is well above the 8-test minimum once the data providers expand
(parameterized rows become individual cases at run time), and it covers both
endpoints with positive and negative paths.

**Intentionally left out:** authentication/authorization (the contract is
silent on it), pagination/filtering (the GET endpoint declares no query
params), and performance/load (out of scope for a contract-level functional
suite). These are noted rather than guessed at in code.

---

## What the abstractions buy at 8 → 80 tests

- **Factory + request builder** — at scale, the cost of payloads is duplication
  and drift. A central baseline means a new required field is added in one
  place, not in every test body.
- **Service layer** — when auth headers, a request envelope, or a path changes,
  one method changes instead of N tests. New operations (PUT/DELETE) slot in as
  new methods.
- **Steps** — the "success envelope" and "validation-error envelope"
  assertions are written once. Tightening a rule (e.g. asserting a new header)
  propagates to all tests for free.
- **Schema helper + schema files** — every success is validated identically,
  and the schema is versionable alongside the contract. This is also the hook
  for contract testing later.
- **Typed response models** — assertions read at the domain level and survive
  field renames with a single edit.

---

## Assumptions (where the contract is silent)

1. **P11 status code** — the contract leaves duplicate-`mbId` as 400 *or* 409.
   The test accepts either and asserts a non-empty `errors` array mentioning
   `mbId`, rather than pinning a specific code.
2. **Content negotiation** — `Content-Type` and `Accept: application/json` are
   applied to every request by the suite config, per the conventions section.
3. **`mbId` randomisation** — the factory randomises `mbId` per build so that,
   against a real stateful environment, independent tests do not collide on the
   uniqueness rule.
4. **`_generated/ApiTesterActions`** — normally produced by `codecept build`.
   A no-op placeholder is committed so the actor class is loadable in a
   read-only repository; a real setup regenerates it.

---

## Improvements for scale and maintainability

- **Data setup/teardown** — once a live environment exists, create fixtures via
  API (or DB seeding) in `_before`/`_after` and clean up created buyers, so
  tests are independent and repeatable.
- **CI integration** — run on every PR with `BASE_URL` injected per environment;
  publish Allure/HTML reports and fail the build on schema mismatches.
- **Parallelization** — split the suite (`codecept run --shard`) once the count
  grows; randomised `mbId` already keeps create-tests collision-safe.
- **Schema versioning / contract testing** — track schemas in version control
  alongside the OpenAPI spec; add consumer-driven contract tests (e.g. Pact) or
  spec-diff checks so a backend change that breaks the contract trips CI.
- **Negative-path expansion** — broaden boundary providers (Unicode names,
  whitespace-only fields, oversized `slackUserId`) as the contract firms up.

---

## How it would run (for reference)

```bash
composer install
cp .env.example .env          # set BASE_URL for the target environment
vendor/bin/codecept build     # regenerate the actor methods
vendor/bin/codecept run Api    # execute against the configured environment
```
