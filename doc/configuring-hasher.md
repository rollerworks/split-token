Configuring the hasher
======================

**Note:** Only the `Argon2SplitTokenFactory` can be configured.

To configure a SplitToken factory pass an associative array of options
to the Factory constructor.

| Option         | Description                                                                       |
|----------------|-----------------------------------------------------------------------------------|
| 'memory_cost'  | amount of memory in bytes that Argon2lib will use while trying to compute a hash. |
| 'time_cost'    | amount of time that Argon2lib will spend trying to compute a hash.                |
| 'threads'      | number of threads that Argon2lib will use.                                        |

```php
$splitTokenFactory = new Argon2SplitTokenFactory([
    'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
    'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
    'threads' => PASSWORD_ARGON2_DEFAULT_THREADS,
]);
```

**Tip:** You can create as many factory instances as needed, not all usage requires
high memory or time cost. The shorter the lifetime of a token the lower you can keep
these values.
