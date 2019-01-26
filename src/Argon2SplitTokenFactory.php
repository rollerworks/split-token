<?php

declare(strict_types=1);

/*
 * This file is part of the Rollerworks SplitToken Component.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Rollerworks\Component\SplitToken;

use DateTimeImmutable;
use ParagonIE\HiddenString\HiddenString;
use function random_bytes;

/**
 * Uses (Lib)sodium Argon2i(d) for hashing the SplitToken verifier.
 *
 * Configuration accepts the following (all integer):
 *
 * 'memory_cost' amount of memory in bytes that Argon2lib will use while trying to compute a hash.
 * 'time_cost'   amount of time that Argon2lib will spend trying to compute a hash.
 * 'threads'     number of threads that Argon2lib will use.
 */
final class Argon2SplitTokenFactory implements SplitTokenFactory
{
    private $config;
    private $defaultExpirationTimestamp;

    /**
     * @param int[] $config
     */
    public function __construct(array $config = [], ?DateTimeImmutable $defaultExpirationTimestamp = null)
    {
        $this->config                     = $config;
        $this->defaultExpirationTimestamp = $defaultExpirationTimestamp;
    }

    public function generate(): SplitToken
    {
        $splitToken = Argon2SplitToken::create(
            // DO NOT ENCODE HERE (always provide as raw binary)!
            new HiddenString(random_bytes((int) SplitToken::TOKEN_CHAR_LENGTH), false, true),
            $this->config
        );

        if ($this->defaultExpirationTimestamp !== null) {
            $splitToken->expireAt($this->defaultExpirationTimestamp);
        }

        return $splitToken;
    }

    public function fromString(string $token): SplitToken
    {
        return Argon2SplitToken::fromString($token);
    }
}
