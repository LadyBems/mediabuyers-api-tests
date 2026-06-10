# Written Evaluation — Media Buyers API Automation

Answers to Part 2. Kept to the point; the suite itself and the README cover the
implementation detail.

---

## 1. Which scenarios did you automate, and why? What did you leave out?

I encoded every acceptance criterion in the contract (G1–G7, P1–P11) as at least
one test, because in a contract-first project the acceptance criteria *are* the
specification — each one is a promise the backend must keep, so each deserves a
guard.

I prioritised, in order:

1. **The happy paths** (GET list valid + schema; POST valid create with id
   generation and field round-trip). If these break, the resource is unusable,
   so they are the highest-value tests.
2. **Validation negatives** (missing required fields, invalid email, initials
   length, name bounds, non-numeric `mbId`, non-boolean `active`). These are
   where APIs most often regress silently, and they are cheap to parameterize.
3. **Stateful rules** (uniqueness on `mbId`), which need ordering and are the
   most environment-dependent.

I deliberately left out **auth** (the contract says nothing about it),
**pagination/filtering** (the GET endpoint declares no query params), and
**performance/load** (not a contract-functional concern). Guessing at these in
code would invent a contract that doesn't exist; I noted them in the README
instead. I also didn't pin the P11 status code to 400 *or* 409, since the
contract explicitly leaves it open.

---

## 2. The abstractions, and what each buys when the suite grows 8 → 80

- **Request builder + factory** — one known-good payload definition. At 80
  tests, adding a required field is a one-line change rather than 80 edits, and
  negative tests stay honest because they mutate exactly one field off a valid
  baseline.
- **Service layer (HTTP boundary)** — the verb + path + envelope for each
  operation lives in one method. Auth, header, or routing changes touch one
  place. New operations (PUT/DELETE) are added as methods, not copied transport
  code.
- **Steps (composite assertions)** — "what a correct success/error looks like"
  is written once. Tightening a rule propagates everywhere for free, which is
  what keeps a large suite consistent.
- **Schema-validation helper + schema files** — guarantees every success is
  validated identically and makes the response contract a versionable artifact.
  It is also the natural hook for contract testing later.
- **Typed response models** — assertions read at the domain level
  (`$buyer->active()`), and a field rename is absorbed in one model rather than
  scattered across array-key accesses.
- **Constants + config** — no magic strings, and environment switching is a
  single variable. This is what lets the same suite run unchanged across local,
  staging, and CI.

The throughline: each abstraction converts an O(N) change into an O(1) change as
the suite grows.

---

## 3. Contract-drift detection

Goal: a backend change to the API automatically triggers test updates and
review, rather than silently passing or failing for the wrong reason.

**Tooling**

- Treat an **OpenAPI/JSON-Schema spec as the single source of truth**, stored in
  version control. The response schemas in this repo are the first step toward
  that.
- Add **spec-diff in CI**: a job that diffs the current published spec against
  the committed copy (e.g. `oasdiff`). A breaking diff fails the build and opens
  a PR.
- Add **consumer-driven contract tests** (Pact) or **schema-response validation
  against the live spec**, so the suite checks responses against the *current*
  spec, not a stale local copy.

**Process**

- The backend publishes the spec as a build artifact; a scheduled/CI job pulls
  it and compares.
- A breaking change blocks merge and notifies QA + the owning backend team via
  the PR.
- Schemas are versioned alongside the contract, so a deliberate change is a
  reviewed commit, not a surprise at test time.

The principle is to make the contract executable on both sides, so drift surfaces
as a failed diff with a clear owner, not as a flaky test.

---

## 4. Tools (including AI) for the QA process

- **Test generation** — AI assistants (e.g. Claude) to scaffold Cest classes,
  data providers, and schemas from a contract or OpenAPI spec, and to suggest
  boundary/negative cases a human might miss. I treat AI output as a draft to
  review, not as final tests, because it can invent assumptions the contract
  doesn't make.
- **Maintenance** — schema-driven validation plus the layered design above, so
  most contract changes are absorbed in one place. AI helps with bulk refactors
  (e.g. propagating a renamed field) and PR review.
- **Flakiness detection** — CI history analytics (e.g. test retry + quarantine
  dashboards, Allure trends) to flag intermittently failing tests; isolate
  state-dependent tests with proper setup/teardown to remove the most common
  flake source.
- **Reporting** — Allure for rich, historical reports; CI status checks gating
  merges; schema-mismatch failures surfaced with the exact failing property.

The reasoning: AI accelerates generation and refactoring, but the durable
guarantees come from the contract being executable (schemas, spec-diff) and from
clean abstractions that keep maintenance cheap.

---

## 5. Most challenging API/E2E automation situation, and how I resolved it

A representative example from my automation work: a messaging-integration test
(Kafka consumer verifying a downstream service) failed intermittently with a
`NullPointerException`. The root cause was a **data-format mismatch in a
Protobuf field** — a timestamp arrived as a structured `{seconds, nanos}` object
where the test expected an ISO string, so deserialization produced a null and
the assertion blew up nondeterministically depending on timing.

Resolution: I reproduced it by isolating the consumer from timing (asserting on a
captured message rather than a live race), pinned the exact field shape against
the schema, and adapted the deserialization/mapping to the real Protobuf
representation. I then added an explicit assertion on the field format so a
future format change fails loudly and specifically, instead of as an opaque NPE.

The lesson I carry into API testing: when a failure is intermittent, the bug is
usually **state or data-shape, not the assertion** — make the data explicit,
remove timing races, and assert on the contract's exact shape so the next
regression is unambiguous.
