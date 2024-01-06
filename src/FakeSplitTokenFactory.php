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
 * Always uses the same non-random value for the SplitToken to speed-up tests.
 *
 * !! THIS IMPLEMENTATION IS NOT SECURE, USE ONLY FOR TESTING !!
 */
final class FakeSplitTokenFactory extends AbstractSplitTokenFactory
{
    public const SELECTOR = '1zUeXUvr4LKymANBB_bLEqiP5GPr-Pha';
    public const VERIFIER = '_OR6OOnV1o8Vy_rWhDoxKNIt';
    public const FULL_TOKEN = self::SELECTOR . self::VERIFIER;

    private string $randomValue;

    public static function randomInstance(): self
    {
        return new self(random_bytes(FakeSplitToken::TOKEN_DATA_LENGTH));
    }

    public function __construct(string $randomValue = null, \DateInterval | string $defaultLifeTime = null)
    {
        parent::__construct($defaultLifeTime);

        $this->randomValue = $randomValue ?? ((string) hex2bin('d7351e5d4bebe0b2b298034107f6cb12a88fe463ebf8f85afce47a38e9d5d68f15cbfad6843a3128d22d'));
    }

    public function generate(\DateTimeImmutable | \DateInterval $expiresAt = null): SplitToken
    {
        return FakeSplitToken::create(new HiddenString($this->randomValue, false, true))
            ->expireAt($this->getExpirationTimestamp($expiresAt));
    }

    public function fromString(string | HiddenString | \Stringable $token): SplitToken
    {
        return FakeSplitToken::fromString($token);
    }
}
