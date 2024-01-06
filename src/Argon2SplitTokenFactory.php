<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Rollerworks\Component\SplitToken;

use ParagonIE\HiddenString\HiddenString;

/**
 * Uses sodium Argon2id for hashing the SplitToken verifier.
 *
 * Configuration accepts the following (all integer):
 *
 * 'memory_cost' amount of memory in bytes that Argon2lib will use while trying to compute a hash.
 * 'time_cost'   amount of time that Argon2lib will spend trying to compute a hash.
 * 'threads'     number of threads that Argon2lib will use.
 */
final class Argon2SplitTokenFactory extends AbstractSplitTokenFactory
{
    /** @param array<string, int> $config */
    public function __construct(private array $config = [], \DateInterval | string $defaultLifeTime = null)
    {
        parent::__construct($defaultLifeTime);
    }

    public function generate(\DateTimeImmutable | \DateInterval $expiresAt = null): SplitToken
    {
        $splitToken = Argon2SplitToken::create(
            // DO NOT ENCODE HERE (always provide as raw binary)!
            new HiddenString(random_bytes(SplitToken::TOKEN_DATA_LENGTH), false, true),
            $this->config
        );

        return $splitToken->expireAt($this->getExpirationTimestamp($expiresAt));
    }

    public function fromString(#[\SensitiveParameter] string | HiddenString | \Stringable $token): SplitToken
    {
        return Argon2SplitToken::fromString($token);
    }
}
