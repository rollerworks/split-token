<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Rollerworks\Component\SplitToken;

/**
 * Don't create this class directly, use {@see Argon2SplitTokenFactory}
 * to create a new instance instead.
 */
final class Argon2SplitToken extends SplitToken
{
    /** @param array<string, int> $config */
    protected function configureHasher(array $config = []): void
    {
        $this->config = array_merge(
            [
                'memory_cost' => \PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
                'time_cost' => \PASSWORD_ARGON2_DEFAULT_TIME_COST,
                'threads' => \PASSWORD_ARGON2_DEFAULT_THREADS,
            ],
            $config
        );
    }

    protected function verifyHash(string $hash, string $verifier): bool
    {
        return password_verify($verifier, $hash);
    }

    /** @codeCoverageIgnore */
    protected function hashVerifier(string $verifier): string
    {
        try {
            $passwordHash = password_hash($verifier, \PASSWORD_ARGON2ID, $this->config);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Unrecoverable password hashing error.', 0, $e);
        }

        return $passwordHash;
    }
}
