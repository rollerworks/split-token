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

/**
 * @internal
 */
final class Argon2SplitTokenFactoryTest extends TestCase
{
    #[Test]
    public function it_generates_a_new_token_on_every_call()
    {
        $factory = new Argon2SplitTokenFactory();
        $splitToken1 = $factory->generate();
        $splitToken2 = $factory->generate();

        self::assertNotEquals($splitToken1->selector(), $splitToken2->selector());
        self::assertNotEquals($splitToken1, $splitToken2);
    }

    #[Test]
    public function it_creates_from_string()
    {
        $factory = new Argon2SplitTokenFactory();
        $splitToken = $factory->generate();
        $fullToken = $splitToken->token()->getString();
        $splitTokenFromString = $factory->fromString($fullToken);

        self::assertTrue($splitTokenFromString->matches($splitToken->toValueHolder()));
    }
}
