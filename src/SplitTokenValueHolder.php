<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Rollerworks\Component\SplitToken;

/**
 * SplitToken keeps SplitToken information for storage.
 *
 * * The selector is used to identify a token, this is a unique random
 *   URI-safe string with a fixed length of {@see SplitToken::SELECTOR_BYTES} bytes.
 *
 * * The verifierHash holds a password hash of a variable
 *   length and is to be validated by a verifier callback.
 *
 * Additionally a SplitTokenValueHolder optionally holds an
 * expiration timestamp and metadata to perform the operation
 * or collect auditing information.
 *
 * The original token is not stored with this value-object.
 */
final class SplitTokenValueHolder
{
    private ?string $selector = null;
    private ?string $verifierHash = null;
    private ?\DateTimeImmutable $expiresAt = null;
    /** @var array<string, scalar> */
    private ?array $metadata = [];

    /** @param array<string, scalar> $metadata */
    public function __construct(string $selector, string $verifierHash, ?\DateTimeImmutable $expiresAt = null, array $metadata = [])
    {
        $this->selector = $selector;
        $this->verifierHash = $verifierHash;
        $this->expiresAt = $expiresAt;
        $this->metadata = $metadata;
    }

    public static function isEmpty(?self $valueHolder): bool
    {
        if ($valueHolder === null) {
            return true;
        }

        // It's possible these values are empty when used as Embedded, because Embedded
        // will always produce an object.
        return $valueHolder->selector === null || $valueHolder->verifierHash === null;
    }

    /**
     * Returns whether the current token (if any) can be replaced with the new token.
     *
     * This methods should only to be used to prevent setting a token when a token
     * was already set, which has not expired, and the same metadata was given (strict checked!).
     *
     * @param array<string, scalar> $expectedMetadata
     */
    public static function mayReplaceCurrentToken(?self $valueHolder, array $expectedMetadata = []): bool
    {
        if ($valueHolder === null || self::isEmpty($valueHolder)) {
            return true;
        }

        if ($valueHolder->isExpired()) {
            return true;
        }

        return $valueHolder->metadata() !== $expectedMetadata;
    }

    public function selector(): ?string
    {
        return $this->selector;
    }

    public function verifierHash(): ?string
    {
        return $this->verifierHash;
    }

    /** @param array<string, scalar> $metadata */
    public function withMetadata(array $metadata): self
    {
        if (self::isEmpty($this)) {
            throw new \RuntimeException('Incomplete TokenValueHolder.');
        }

        return new self($this->selector, $this->verifierHash, $this->expiresAt, $metadata);
    }

    /** @return array<string, scalar> */
    public function metadata(): array
    {
        return $this->metadata ?? [];
    }

    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt->getTimestamp() < ($now ?? new \DateTimeImmutable())->getTimestamp();
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Compares if both objects are the same.
     *
     * Warning this method leaks timing information and the expiration date is ignored!
     * This method should only be used to check if a new token is provided.
     */
    public function equals(self $other): bool
    {
        return $other->selector === $this->selector
               && $other->verifierHash === $this->verifierHash
               && $other->metadata === $this->metadata;
    }
}
