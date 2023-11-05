<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Rollerworks\Component\SplitToken\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\SplitToken\Argon2SplitTokenFactory;
use Rollerworks\Component\SplitToken\SplitToken;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

/**
 * @internal
 */
final class Argon2SplitTokenFactoryTest extends TestCase
{
    use ClockSensitiveTrait;

    #[Test]
    public function it_generates_a_new_token_on_every_call(): void
    {
        $factory = new Argon2SplitTokenFactory();
        $splitToken1 = $factory->generate();
        $splitToken2 = $factory->generate();

        self::assertNotEquals($splitToken1->selector(), $splitToken2->selector());
        self::assertNotEquals($splitToken1, $splitToken2);
    }

    #[Test]
    public function it_generates_with_default_expiration(): void
    {
        $factory = new Argon2SplitTokenFactory(defaultLifeTime: new \DateInterval('P1D'));
        $factory->setClock(self::mockTime('2023-10-05T20:00:00+02:00'));

        self::assertExpirationEquals('2023-10-06T20:00:00', $factory->generate());
        self::assertExpirationEquals('2023-10-07T20:00:00', $factory->generate(new \DateInterval('P2D')));
        self::assertExpirationEquals('2019-10-05T20:00:00', $factory->generate(new \DateTimeImmutable('2019-10-05T20:00:00+02:00')));

        $factory = new Argon2SplitTokenFactory();
        $factory->setClock(self::mockTime('2023-10-05T20:00:00+02:00'));

        self::assertNull($factory->generate()->getExpirationTime());
        self::assertExpirationEquals('2023-10-07T20:00:00', $factory->generate(new \DateInterval('P2D')));
    }

    #[Test]
    public function it_generates_with_default_expiration_as_string(): void
    {
        $factory = new Argon2SplitTokenFactory(defaultLifeTime: 'P1D');
        $factory->setClock(self::mockTime('2023-10-05T20:00:00+02:00'));

        self::assertExpirationEquals('2023-10-06T20:00:00', $factory->generate());
    }

    private static function assertExpirationEquals(string $expected, SplitToken $actual): void
    {
        self::assertSame($expected, $actual->getExpirationTime()->format('Y-m-d\TH:i:s'));
    }

    #[Test]
    public function it_creates_from_string(): void
    {
        $factory = new Argon2SplitTokenFactory();
        $splitToken = $factory->generate();
        $fullToken = $splitToken->token()->getString();
        $splitTokenFromString = $factory->fromString($fullToken);

        self::assertTrue($splitTokenFromString->matches($splitToken->toValueHolder()));
    }
}
