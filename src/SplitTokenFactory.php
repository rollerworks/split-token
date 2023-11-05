<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Rollerworks\Component\SplitToken;

use ParagonIE\HiddenString\HiddenString;

interface SplitTokenFactory
{
    /**
     * Generates a new SplitToken object.
     *
     * Example:
     *
     * ```
     * return SplitToken::create(
     *     // DO NOT ENCODE HERE (always provide the random data as raw binary)!
     *     new HiddenString(\random_bytes(SplitToken::TOKEN_CHAR_LENGTH), false, true),
     *     $id
     * );
     * ```
     *
     * @see HiddenString
     */
    public function generate(\DateTimeImmutable | \DateInterval $expiresAt = null): SplitToken;

    /**
     * Recreates a SplitToken object from a token-string
     * (provided by either request attribute).
     *
     * Example:
     *
     * ```
     * return SplitToken::fromString($token);
     * ```
     */
    public function fromString(string $token): SplitToken;
}
