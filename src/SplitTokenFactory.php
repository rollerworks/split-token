<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Rollerworks\Component\SplitToken;


interface SplitTokenFactory
{
    /**
     * Generates a new SplitToken object.
     *
     * Example:
     *
     * ```
     * return SplitToken::create(
     *     new HiddenString(\random_bytes(SplitToken::TOKEN_CHAR_LENGTH), false, true), // DO NOT ENCODE HERE (always provide as raw binary)!
     *     $id
     * );
     * ```
     *
     * @see \ParagonIE\Halite\HiddenString
     */
    public function generate(): SplitToken;

    /**
     * Recreates a SplitToken object from a HiddenString (provided by eg. a user).
     *
     * Example:
     *
     * ```
     * return SplitToken::fromString($token);
     * ```
     */
    public function fromString(string $token): SplitToken;
}
