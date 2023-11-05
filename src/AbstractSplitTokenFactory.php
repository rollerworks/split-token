<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Rollerworks\Component\SplitToken;

use Psr\Clock\ClockInterface;

abstract class AbstractSplitTokenFactory implements SplitTokenFactory
{
    private ?\DateInterval $defaultLifeTime = null;
    private ?ClockInterface $clock;

    public function __construct(\DateInterval | string $defaultLifeTime = null)
    {
        if (\is_string($defaultLifeTime)) {
            $defaultLifeTime = new \DateInterval($defaultLifeTime);
        }

        $this->defaultLifeTime = $defaultLifeTime;
    }

    #[Required]
    public function setClock(ClockInterface $clock): void
    {
        $this->clock = $clock;
    }

    final protected function getExpirationTimestamp(\DateTimeImmutable | \DateInterval $expiration = null): ?\DateTimeImmutable
    {
        if ($expiration instanceof \DateTimeImmutable) {
            return $expiration;
        }

        $expiration ??= $this->defaultLifeTime;

        if ($expiration !== null) {
            return (isset($this->clock) ? $this->clock->now() : new \DateTimeImmutable('now'))->add($expiration);
        }

        return null;
    }
}
