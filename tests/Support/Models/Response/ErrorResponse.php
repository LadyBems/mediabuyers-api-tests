<?php

declare(strict_types=1);

namespace Tests\Support\Models\Response;

/**
 * Typed view over a 400 validation-error response.
 *
 * The contract wraps validation errors in a top-level "errors" array, each
 * item carrying a "detail" string. This model lets negative tests ask
 * meaningful questions ("does any error mention this field / this text?")
 * instead of manually walking the decoded structure.
 */
final class ErrorResponse
{
    /** @param array<int, array<string, mixed>> $errors */
    public function __construct(private readonly array $errors)
    {
    }

    /** @param array<string, mixed> $body the full decoded response body */
    public static function fromBody(array $body): self
    {
        return new self($body['errors'] ?? []);
    }

    /** @return string[] all detail messages, in order */
    public function details(): array
    {
        return array_map(
            static fn (array $error): string => (string) ($error['detail'] ?? ''),
            $this->errors
        );
    }

    /** True if any error detail contains the given substring (case-insensitive). */
    public function hasDetailContaining(string $needle): bool
    {
        foreach ($this->details() as $detail) {
            if (stripos($detail, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    public function count(): int
    {
        return count($this->errors);
    }
}
