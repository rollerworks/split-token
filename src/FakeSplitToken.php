<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Rollerworks\Component\SplitToken;

use function sha1;

/**
 * !! THIS IMPLEMENTATION IS NOT SECURE, USE ONLY FOR TESTING !!
 */
final class FakeSplitToken extends SplitToken
{
    protected function verifyHash(string $hash, string $verifier): bool
    {
        $hashVerifier = $this->hashVerifier($verifier);

        return $hash === $hashVerifier;
    }

    protected function hashVerifier(string $verifier): string
    {
        return sha1($verifier);
    }
}
