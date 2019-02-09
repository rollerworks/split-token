<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Rollerworks\Component\SplitToken;

use DateTimeImmutable;
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\ConstantTime\Binary;
use ParagonIE\HiddenString\HiddenString;
use RuntimeException;
use function sodium_memzero;
use function sprintf;

/**
 * A split-token value-object.
 *
 * Caution before working on this class understand that any change can
 * potentially introduce a security problem. Please consult a security
 * expert before accepting these changes as-is:
 *
 * * The selector and verifier are base64-uri-safe encoded using a constant-time
 *   encoder. Do not replace these with a regular encoder as this leaks timing
 *   information, making it possible to perform side-channel attacks.
 *
 * * The selector is used as ID to identify the token, leaking this value
 *   has no negative effect. The index of the storage already leaks timing.
 *
 * * The verifier is used _as a password_ to authenticate the token,
 *   only the 'full token' has the original value. The storage holds
 *   a crypto hashed version of the verifier.
 *
 * * When validating the token, the provided verifier is crypto
 *   compared in *constant-time* for equality.
 *
 * The 'full token' is to be shared with the receiver only!
 *
 * THE TOKEN HOLDS THE ORIGINAL "VERIFIER", DO NOT STORE THE TOKEN
 * IN A STORAGE DIRECTLY, UNLESS A PROPER FORM OF ENCRYPTION IS USED!
 *
 * Example (for illustration):
 *
 * <code>
 * // Create
 * $splitTokenFactory = ...;
 *
 * $token = $splitTokenFactory->create();
 *
 * // The $authToken is to be shared with the receiver (eg. the user) only.
 * // And is URI safe.
 * //
 * // DO NOT STORE "THIS" VALUE IN THE DATABASE! Store the selector and verifier-hash instead.
 * $authToken = $token->token(); // HiddenString
 *
 * $holder = $token->toValueHolder();
 *
 * // UPDATE site_user
 * // SET
 * //   recovery_selector = $holder->selector(),
 * //   recovery_verifier = $holder->verifierHash(),
 * //   recovery_expires_at = $holder->expiresAt(),
 * //   recovery_metadata = $holder->metadata(),
 * //   recovery_timestamp = NOW()
 * // WHERE user_id = ...
 *
 *
 * // Verification step:
 * $token = $splitTokenFactory->fromString($_GET['token']);
 *
 * // $result = SELECT user_id, recover_verifier, recovery_expires_at, recovery_metadata WHERE recover_selector = $token->selector()
 * $holder = new SplitTokenValueHolder($token->selector(), $result['recovery_verifier'], $result['recovery_expires_at'], $result['recovery_metadata']);
 *
 * $accepted = $token->matches($holder);
 * <code>
 *
 * Note: Invoking toValueHolder() doesn't work for a reconstructed SplitToken object.
 */
abstract class SplitToken
{
    public const SELECTOR_BYTES    = 24;
    public const VERIFIER_BYTES    = 18;
    public const TOKEN_DATA_LENGTH = (self::VERIFIER_BYTES + self::SELECTOR_BYTES);
    public const TOKEN_CHAR_LENGTH = (self::SELECTOR_BYTES * 4 / 3) + (self::VERIFIER_BYTES * 4 / 3);

    /** @var array */
    protected $config = [];

    /** @var HiddenString */
    private $token;

    /** @var string */
    private $selector;

    /** @var string */
    private $verifier;

    /** @var string|null */
    private $verifierHash;

    /** @var DateTimeImmutable|null */
    private $expiresAt;

    private function __construct(HiddenString $token, string $selector, string $verifier)
    {
        $this->token    = $token;
        $this->selector = $selector;
        $this->verifier = $verifier;
    }

    /**
     * Creates a new SplitToken object based of the $token.
     *
     * The $randomBytes argument must provide a crypto-random string (wrapped in
     * a HiddenString object) of exactly {@see static::getLength()} bytes.
     *
     * @param mixed[] $config Configuration for the hasher method (implementation specific)
     *
     * @return static
     */
    public static function create(HiddenString $randomBytes, array $config = [])
    {
        $bytesString = $randomBytes->getString();

        if (Binary::safeStrlen($bytesString) < self::TOKEN_DATA_LENGTH) {
            // Don't zero memory as the value is invalid.
            throw new RuntimeException(sprintf('Invalid token-data provided, expected exactly %s bytes.', static::VERIFIER_BYTES + static::SELECTOR_BYTES));
        }

        $selector = Base64UrlSafe::encode(Binary::safeSubstr($bytesString, 0, self::SELECTOR_BYTES));
        $verifier = Base64UrlSafe::encode(Binary::safeSubstr($bytesString, self::SELECTOR_BYTES, self::VERIFIER_BYTES));
        $token    = new HiddenString($selector . $verifier, false, true);

        $instance = new static($token, $selector, $verifier);
        $instance->configureHasher($config);

        $instance->verifierHash = $instance->hashVerifier($instance->verifier);

        sodium_memzero($instance->verifier);
        sodium_memzero($bytesString);

        return $instance;
    }

    /**
     * @return static
     */
    public function expireAt(?DateTimeImmutable $expiresAt = null)
    {
        $instance            = clone $this;
        $instance->expiresAt = $expiresAt;

        return $instance;
    }

    /**
     * Recreates a SplitToken object from a string.
     *
     * Note: The provided $token is zeroed from memory when it's length is valid.
     *
     * @return static
     */
    final public static function fromString(string $token)
    {
        if (Binary::safeStrlen($token) < self::TOKEN_CHAR_LENGTH) {
            // Don't zero memory as the value is invalid.
            throw new RuntimeException('Invalid token provided.');
        }

        $selector = Binary::safeSubstr($token, 0, 32);
        $verifier = Binary::safeSubstr($token, 32);

        $instance = new static(new HiddenString($token), $selector, $verifier);
        // Don't (re)generate as this needs the salt of the stored hash.
        $instance->verifierHash = null;

        sodium_memzero($token);

        return $instance;
    }

    /**
     * Returns the selector to identify the token in storage.
     */
    public function selector(): string
    {
        return $this->selector;
    }

    /**
     * Returns the full token (selector + verifier) for authentication.
     */
    public function token(): HiddenString
    {
        return $this->token;
    }

    /**
     * Verifies this SplitToken against a (stored) SplitTokenValueHolder.
     *
     * This method is to be used once the SplitToken is reconstructed
     * from a user-provided string.
     */
    final public function matches(?SplitTokenValueHolder $token): bool
    {
        if (SplitTokenValueHolder::isEmpty($token)) {
            return false;
        }

        if ($token->isExpired() || $token->selector() !== $this->selector) {
            return false;
        }

        /** @psalm-suppress PossiblyNullArgument */
        return $this->verifyHash($token->verifierHash(), $this->verifier);
    }

    /**
     * Produce a new SplitTokenValue instance.
     *
     * Note: This method doesn't work when reconstructed from a string.
     *
     * @param mixed[] $metadata Metadata for storage
     */
    public function toValueHolder(array $metadata = []): SplitTokenValueHolder
    {
        if ($this->verifierHash === null) {
            throw new RuntimeException('toValueHolder() does not work SplitToken object created with fromString().');
        }

        return new SplitTokenValueHolder($this->selector, $this->verifierHash, $this->expiresAt, $metadata);
    }

    /**
     * Compares if both objects are the same.
     *
     * Warning this method leaks timing information and the expiration date is ignored!
     */
    public function equals(self $other): bool
    {
        return $other->selector === $this->selector && $other->verifierHash === $this->verifierHash;
    }

    public function getExpirationTime(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * This method is called in create() before the verifier is hashed,
     * allowing to set-up configuration for the hashing method.
     */
    protected function configureHasher(array $config)
    {
        // no-op
    }

    /**
     * Checks if the provided hash equals the provided verifier.
     *
     * This implementation must use a time-safe hash-comparator.
     * Either: sodium_crypto_pwhash_str_verify($hash, $verifier)
     *   or hash_equals($hash, static::hashVerifier($verifier))
     */
    abstract protected function verifyHash(string $hash, string $verifier): bool;

    /**
     * Produces a hashed version of the verifier.
     */
    abstract protected function hashVerifier(string $verifier): string;
}
