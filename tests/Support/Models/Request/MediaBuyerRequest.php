<?php

declare(strict_types=1);

namespace Tests\Support\Models\Request;

/**
 * Typed model for a POST /api/mediabuyers request body.
 *
 * This is a builder, not a bag of magic arrays. Tests construct payloads
 * through the factory (see MediaBuyerFactory) and then tweak single fields
 * fluently for negative cases:
 *
 *     MediaBuyerFactory::valid()->withEmail('not-an-email')->toArray();
 *
 * Why a model and not raw JSON in the test:
 *  - One definition of the request shape; a contract field rename changes here.
 *  - Negative cases express intent ("withInitials('TOO LONG')") rather than
 *    burying the mutation inside a literal.
 *  - Supports omitting a field entirely (for required-field tests) distinctly
 *    from sending it as null.
 */
final class MediaBuyerRequest
{
    /**
     * Tracks which fields are "set". A field absent from this map is omitted
     * from the serialized body entirely — this is how we model "missing
     * required field" without confusing it with an explicit null.
     *
     * @var array<string, mixed>
     */
    private array $fields = [];

    public function withMbId(mixed $mbId): self
    {
        $this->fields['mbId'] = $mbId;
        return $this;
    }

    public function withInitials(mixed $initials): self
    {
        $this->fields['initials'] = $initials;
        return $this;
    }

    public function withName(mixed $name): self
    {
        $this->fields['name'] = $name;
        return $this;
    }

    public function withEmail(mixed $email): self
    {
        $this->fields['email'] = $email;
        return $this;
    }

    public function withSlackUserId(mixed $slackUserId): self
    {
        $this->fields['slackUserId'] = $slackUserId;
        return $this;
    }

    public function withActive(mixed $active): self
    {
        $this->fields['active'] = $active;
        return $this;
    }

    /**
     * Remove a field from the payload entirely, modelling an omitted required
     * field. Returns a new logical state on the same instance for fluency.
     */
    public function without(string $field): self
    {
        unset($this->fields[$field]);
        return $this;
    }

    /** @return array<string, mixed> the JSON-serializable request body */
    public function toArray(): array
    {
        return $this->fields;
    }
}
