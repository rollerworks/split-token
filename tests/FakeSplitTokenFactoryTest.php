<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\SplitToken\FakeSplitTokenFactory;
use Rollerworks\Component\SplitToken\SplitToken;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

/**
 * @internal
 */
final class FakeSplitTokenFactoryTest extends TestCase
{
    use ClockSensitiveTrait;

    #[Test]
    public function it_generates_a_same_token_on_every_call(): void
    {
        $factory = new FakeSplitTokenFactory();
        $splitToken1 = $factory->generate();
        $splitToken2 = $factory->generate();

        self::assertEquals($splitToken1->selector(), $splitToken2->selector());
        self::assertEquals($splitToken1, $splitToken2);

        $factory2 = new FakeSplitTokenFactory();
        $splitToken1 = $factory->generate();
        $splitToken2 = $factory2->generate();

        self::assertEquals($splitToken1->selector(), $splitToken2->selector());
        self::assertEquals($splitToken1, $splitToken2);
    }

    #[Test]
    public function it_generates_a_new_token_when_passed(): void
    {
        $splitToken1 = FakeSplitTokenFactory::randomInstance()->generate();
        $splitToken2 = FakeSplitTokenFactory::randomInstance()->generate();

        self::assertNotEquals($splitToken1->selector(), $splitToken2->selector());
        self::assertNotEquals($splitToken1, $splitToken2);
    }

    #[Test]
    public function it_generates_with_default_expiration_date(): void
    {
        $factory = new FakeSplitTokenFactory(defaultLifeTime: new \DateInterval('P1D'));
        $factory->setClock(self::mockTime('2023-10-05T20:00:00+02:00'));

        self::assertExpirationEquals('2023-10-06T20:00:00', $factory->generate());
        self::assertExpirationEquals('2023-10-07T20:00:00', $factory->generate(new \DateInterval('P2D')));
        self::assertExpirationEquals('2019-10-05T20:00:00', $factory->generate(new \DateTimeImmutable('2019-10-05T20:00:00+02:00')));

        $factory = new FakeSplitTokenFactory();
        $factory->setClock(self::mockTime('2023-10-05T20:00:00+02:00'));

        self::assertNull($factory->generate()->getExpirationTime());
        self::assertExpirationEquals('2023-10-07T20:00:00', $factory->generate(new \DateInterval('P2D')));
    }

    private static function assertExpirationEquals(string $expected, SplitToken $actual): void
    {
        self::assertNotNull($actual->getExpirationTime());
        self::assertSame($expected, $actual->getExpirationTime()->format('Y-m-d\TH:i:s'));
    }

    #[Test]
    public function it_creates_from_string(): void
    {
        $factory = new FakeSplitTokenFactory();
        $splitToken = $factory->generate();

        $fullToken = $splitToken->token()->getString();
        $splitTokenFromString = $factory->fromString($fullToken);

        self::assertTrue($splitTokenFromString->matches($splitToken->toValueHolder()));
    }

    #[Test]
    public function it_creates_from_string_with_mock_provided_selector(): void
    {
        $factory = new FakeSplitTokenFactory();
        $splitToken = $factory->generate();

        $fullToken = FakeSplitTokenFactory::FULL_TOKEN;
        $fullTokenStr = $splitToken->token()->getString();

        $splitTokenFromString = $factory->fromString($fullToken);
        $splitTokenFromString2 = $factory->fromString($fullTokenStr);

        self::assertEquals($fullTokenStr, $fullToken);
        self::assertTrue($splitTokenFromString->matches($splitToken->toValueHolder()));
        self::assertTrue($splitTokenFromString2->matches($splitToken->toValueHolder()));
    }

    #[Test]
    public function it_creates_from_hidden_string(): void
    {
        $factory = new FakeSplitTokenFactory();
        $splitToken = $factory->generate();

        $splitTokenFromString = $factory->fromString($splitToken->token());
        self::assertTrue($splitTokenFromString->matches($splitToken->toValueHolder()));
    }

    #[Test]
    public function it_creates_from_stringable_object(): void
    {
        $factory = new FakeSplitTokenFactory();
        $splitToken = $factory->generate();

        $stringObj = new class($splitToken->token()->getString()) implements \Stringable {
            public function __construct(private string $value) {}

            public function __toString(): string
            {
                return $this->value;
            }
        };

        $splitTokenFromString = $factory->fromString($stringObj);
        self::assertTrue($splitTokenFromString->matches($splitToken->toValueHolder()));
    }
}
