UPGRADE
=======

## Upgrade from 1.0.0-BETA1

* The `Rollerworks\Component\SplitToken\SplitTokenFactory::fromString()` method
  now also accepts a `Stringable` object or `HiddenString`. Unless a custom factory
  implementation is used this should not effect your code.

## Upgrade from 0.1.2

* Support for PHP 8.1 and lower was dropped;

* Now always uses Aragon2id instead of Aragon2i;

* The `Argon2SplitTokenFactory` now expects a `DateInterval` or string with a date-interval
  as second argument to constructor. Previously this required a `DateTimeImmutable`;

* The `SplitTokenFactory::generate()` now allows a `DateTimeImmutable` or `DateInterval`
  which is calculated relative to "now" or the `now()` as provided by the `ClockInterface`.

  Use `setClock()` on the factory to set an active Clock instance, this is also the recommended
  way for using the `FakeSplitTokenFactory()`.
