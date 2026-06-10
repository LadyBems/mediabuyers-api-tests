<?php

declare(strict_types=1);

namespace Tests\Support\Models\Response;

/**
 * Typed view over a single Media Buyer object returned by the API.
 *
 * Wrapping the decoded array in a model gives tests typed accessors
 * ($buyer->active() returns int) instead of reaching into associative arrays
 * with string keys. When the contract adds or renames a field, the change is
 * localised here rather than scattered across assertions.
 */
final class MediaBuyerResponse
{
    /** @param array<string, mixed> $data a single media-buyer object */
    public function __construct(private readonly array $data)
    {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function id(): int
    {
        return (int) $this->data['id'];
    }

    public function mbId(): string
    {
        return (string) $this->data['mbId'];
    }

    public function initials(): ?string
    {
        return isset($this->data['initials']) ? (string) $this->data['initials'] : null;
    }

    public function name(): string
    {
        return (string) $this->data['name'];
    }

    public function email(): string
    {
        return (string) $this->data['email'];
    }

    public function slackUserId(): ?string
    {
        return isset($this->data['slackUserId']) ? (string) $this->data['slackUserId'] : null;
    }

    public function active(): int
    {
        return (int) $this->data['active'];
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->data);
    }

    /** @return array<string, mixed> */
    public function raw(): array
    {
        return $this->data;
    }
}
