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

use RuntimeException;
use const PASSWORD_ARGON2_DEFAULT_MEMORY_COST;
use const PASSWORD_ARGON2_DEFAULT_THREADS;
use const PASSWORD_ARGON2_DEFAULT_TIME_COST;
use const PASSWORD_ARGON2I;
use function array_merge;
use function password_hash;
use function password_verify;

final class Argon2SplitToken extends SplitToken
{
    protected function configureHasher(array $config = [])
    {
        $this->config = array_merge(
            [
                'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
                'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
                'threads' => PASSWORD_ARGON2_DEFAULT_THREADS,
            ],
            $config
        );
    }

    protected function verifyHash(string $hash, string $verifier): bool
    {
        return password_verify($verifier, $hash);
    }

    protected function hashVerifier(string $verifier): string
    {
        $passwordHash = password_hash($verifier, PASSWORD_ARGON2I, $this->config);

        if ($passwordHash === false) {
            throw new RuntimeException('Unrecoverable password hashing error.');
        }

        return $passwordHash;
    }
}
