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

namespace Rollerworks\Component\SplitToken\Tests;

use DateTimeImmutable;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\SplitToken\Argon2SplitToken as SplitToken;
use RuntimeException;
use function hex2bin;

/**
 * @internal
 */
final class Argon2SplitTokenTest extends TestCase
{
    private const FULL_TOKEN = '1zUeXUvr4LKymANBB_bLEqiP5GPr-Pha_OR6OOnV1o8Vy_rWhDoxKNIt';
    private const SELECTOR   = '1zUeXUvr4LKymANBB_bLEqiP5GPr-Pha';

    private static $randValue;

    /**
     * @beforeClass
     */
    public static function createRandomBytes()
    {
        self::$randValue = new HiddenString(hex2bin('d7351e5d4bebe0b2b298034107f6cb12a88fe463ebf8f85afce47a38e9d5d68f15cbfad6843a3128d22d'), false, true);
    }

    /**
     * @test
     */
    public function it_validates_the_correct_length()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid token-data provided, expected exactly 42 bytes.');

        SplitToken::create(new HiddenString('NanananaBatNan', false, true));
    }

    /**
     * @test
     */
    public function it_creates_a_split_token_without_id()
    {
        $splitToken = SplitToken::create(self::$randValue);

        self::assertEquals(self::FULL_TOKEN, $token  = $splitToken->token()->getString());
        self::assertEquals(self::SELECTOR, $selector = $splitToken->selector());
    }

    /**
     * @test
     */
    public function it_creates_a_split_token_with_id()
    {
        $splitToken = SplitToken::create($fullToken = self::$randValue);

        self::assertEquals(self::FULL_TOKEN, $token  = $splitToken->token()->getString());
        self::assertEquals(self::SELECTOR, $selector = $splitToken->selector());
    }

    /**
     * @test
     */
    public function it_compares_two_split_tokens()
    {
        $splitToken1 = SplitToken::create(self::$randValue);

        self::assertTrue($splitToken1->equals($splitToken1));
        self::assertTrue($splitToken1->equals($splitToken1->expireAt(new DateTimeImmutable('+5 seconds'))));
        self::assertFalse($splitToken1->equals(SplitToken::create(self::$randValue)));
    }

    /**
     * @test
     */
    public function it_creates_a_split_token_with_custom_config()
    {
        $splitToken = SplitToken::create(self::$randValue, [
            'memory_cost' => 512,
            'time_cost' => 1,
            'threads' => 1,
        ]);

        self::assertRegExp('/^\$argon2[id]+\$v=19\$m=512,t=1,p=1/', $token = $splitToken->toValueHolder()->verifierHash());
    }

    /**
     * @test
     */
    public function it_produces_a_SplitTokenValueHolder()
    {
        $splitToken = SplitToken::create(self::$randValue);

        $value = $splitToken->toValueHolder();

        self::assertEquals($splitToken->selector(), $value->selector());
        self::assertStringStartsWith('$argon2i', $value->verifierHash());
        self::assertEquals([], $value->metadata());
        self::assertFalse($value->isExpired());
        self::assertFalse($value->isExpired(new DateTimeImmutable('-5 minutes')));
    }

    /**
     * @test
     */
    public function it_produces_a_SplitTokenValueHolder_with_metadata()
    {
        $splitToken = SplitToken::create(self::$randValue);
        $value      = $splitToken->toValueHolder(['he' => 'now']);

        self::assertStringStartsWith('$argon2i', $value->verifierHash());
        self::assertEquals(['he' => 'now'], $value->metadata());
    }

    /**
     * @test
     */
    public function it_produces_a_SplitTokenValueHolder_with_expiration()
    {
        $date       = new DateTimeImmutable('+5 minutes');
        $splitToken = SplitToken::create($fullToken = self::$randValue)->expireAt($date);

        $value = $splitToken->toValueHolder();

        self::assertTrue($value->isExpired($date->modify('+10 minutes')));
        self::assertFalse($value->isExpired($date));
        self::assertEquals([], $value->metadata());
    }

    /**
     * @test
     */
    public function it_reconstructs_from_string()
    {
        $splitTokenReconstituted = SplitToken::fromString(self::FULL_TOKEN);

        self::assertEquals(self::FULL_TOKEN, $splitTokenReconstituted->token()->getString());
        self::assertEquals(self::SELECTOR, $splitTokenReconstituted->selector());
    }

    /**
     * @test
     */
    public function it_fails_when_creating_holder_with_string_constructed()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('toValueHolder() does not work SplitToken object created with fromString().');

        SplitToken::fromString(self::FULL_TOKEN)->toValueHolder();
    }

    /**
     * @test
     */
    public function it_verifies_SplitToken()
    {
        // Stored.
        $splitTokenHolder = SplitToken::create(self::$randValue)->toValueHolder();

        // Reconstructed.
        $fromString = SplitToken::fromString(self::FULL_TOKEN);

        self::assertTrue($fromString->matches($splitTokenHolder));
    }

    /**
     * @test
     */
    public function it_verifies_SplitToken_from_string_and_no_current_token_set()
    {
        $fromString = SplitToken::fromString(self::FULL_TOKEN);

        self::assertFalse($fromString->matches(null));
    }

    /**
     * @test
     */
    public function it_verifies_SplitToken_from_string_selector()
    {
        // Stored.
        $splitTokenHolder = SplitToken::create(self::$randValue)->toValueHolder();

        // Reconstructed.
        $fromString = SplitToken::fromString('12UeXUvr4LKymANBB_bLEqiP5GPr-Pha_OR6OOnV1o8Vy_rWhDoxKNIt');

        self::assertFalse($fromString->matches($splitTokenHolder));
        self::assertFalse($fromString->matches($splitTokenHolder));
    }

    /**
     * @test
     */
    public function it_verifies_SplitToken_from_string_with_expiration()
    {
        // Stored.
        $splitTokenHolder = SplitToken::create(self::$randValue)
            ->expireAt(new DateTimeImmutable('-5 minutes'))
            ->toValueHolder();

        // Reconstructed.
        $fromString = SplitToken::fromString(self::FULL_TOKEN);

        self::assertFalse($fromString->matches($splitTokenHolder));
        self::assertFalse($fromString->matches($splitTokenHolder));
    }
}
