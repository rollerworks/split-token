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
use Doctrine\Instantiator\Instantiator;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\SplitToken\SplitTokenValueHolder;

/**
 * @internal
 */
final class SplitTokenValueHolderTest extends TestCase
{
    private const SELECTOR = '1zUeXUvr4LKymANBB_bLEqiP5GPr-Pha';
    private const VERIFIER = '_OR6OOnV1o8Vy_rWhDoxKNIt';

    /** @test */
    public function its_empty_when_instantiated_from_storage(): void
    {
        $instance = $this->createHolderInstance();

        self::assertNull($instance->selector());
        self::assertNull($instance->verifierHash());
        self::assertNull($instance->expiresAt());
        self::assertEquals([], $instance->metadata());
        self::assertTrue(SplitTokenValueHolder::isEmpty($instance));
        self::assertTrue(SplitTokenValueHolder::isEmpty(null));
    }

    private function createHolderInstance(): SplitTokenValueHolder
    {
        return (new Instantiator())->instantiate(SplitTokenValueHolder::class);
    }

    /** @test */
    public function its_not_empty_with_data(): void
    {
        $instance = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER);

        self::assertNotNull($instance->selector());
        self::assertNotNull($instance->verifierHash());
        self::assertNull($instance->expiresAt());
        self::assertEquals([], $instance->metadata());
        self::assertFalse(SplitTokenValueHolder::isEmpty($instance));
    }

    /** @test */
    public function it_allows_to_replace_current(): void
    {
        self::assertTrue(SplitTokenValueHolder::mayReplaceCurrentToken($this->createHolderInstance()));
    }

    /** @test */
    public function it_allows_to_replace_current_token_when_expired(): void
    {
        self::assertTrue(SplitTokenValueHolder::mayReplaceCurrentToken(new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER, new DateTimeImmutable('-100 seconds'))));
    }

    /** @test */
    public function it_allows_to_replace_current_token_when_metadata_mismatches(): void
    {
        self::assertTrue(SplitTokenValueHolder::mayReplaceCurrentToken(new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER, null, ['foo' => 'me once']), ['shame' => 'on you']));
    }

    /** @test */
    public function it_produces_a_new_object_when_changing_metadata(): void
    {
        $current = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER, new DateTimeImmutable('+10 seconds'));
        $second  = $current->withMetadata(['foo' => 'me twice']);

        self::assertNotSame($current, $second);
        self::assertEquals([], $current->metadata());
        self::assertEquals($current->selector(), $second->selector());
        self::assertEquals($current->verifierHash(), $second->verifierHash());
        self::assertEquals($current->expiresAt(), $second->expiresAt());
        self::assertEquals(['foo' => 'me twice'], $second->metadata());
    }

    /** @test */
    public function it_returns_if_expired(): void
    {
        $instance = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER);

        self::assertFalse($instance->isExpired());
        self::assertFalse($instance->isExpired(new DateTimeImmutable('+10 seconds')));

        $instance = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER, new DateTimeImmutable('+10 seconds'));

        self::assertFalse($instance->isExpired());
        self::assertFalse($instance->isExpired(new DateTimeImmutable('-15 seconds')));
        self::assertTrue($instance->isExpired(new DateTimeImmutable('+12 seconds')));
        self::assertTrue($instance->isExpired(new DateTimeImmutable('+12 seconds')));
    }

    /** @test */
    public function it_equals_other_objects(): void
    {
        $current        = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER);
        $second         = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER);
        $withExpiration = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER, new DateTimeImmutable('+5 seconds'));

        self::assertTrue($current->equals($second));
        self::assertTrue($current->equals($current));
        self::assertTrue($second->equals($current));
        self::assertTrue($second->equals($withExpiration));

        self::assertTrue($current->equals($current->withMetadata([])));
        self::assertFalse($current->equals($current->withMetadata(['shame' => 'on me'])));
    }
}
