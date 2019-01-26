Rollerworks SplitToken Component
================================

SplitToken provides a Token-Based Authentication Protocol without Side-Channels.

This technique is based of [Split Tokens: Token-Based Authentication Protocols without Side-Channels](https://paragonie.com/blog/2017/02/split-tokens-token-based-authentication-protocols-without-side-channels).

SplitToken-Based Authentication is best used for password resetting or one-time
single-logon. 

While possible, this technique is not recommended as a replacement for 
OAuth or Json Web Tokens.

## Introduction

Unlike _traditional_ Token-Based Authentication Protocols a SplitToken consists
of two parts: The **selector** (used in the query) and the **verifier**
(not used in the query).

* The selector is a 24 bytes fixed-length random string, which used as an identifier.
  You can safely create an unique index for field.

* The verifier works as a password and is only provided to the user,
  the database only holds a salted (cryptographic) hash of the verifier.
  
  The length of this value is heavily dependent on the used hashing algorithm
  and should not be hardcoded.
  
The full token is provided to the user or recipient and functions as a combined 
identifier (selector) and password (verifier).

**Caution: You NEVER store the full token as-is!** You only store the selector,
and a (cryptographic) hash of the verifier.

## Requirements

PHP 7.2 with the (lib)sodium extension enabled.

## Installation

To install this package, add `rollerworks/split-token` to your composer.json

```bash
$ php composer.phar require rollerworks/split-token
```

Now, Composer will automatically download all required files, and install them
for you.

**Caution:** There is no stable version of this library yet, while no major changes
are expected you are advised to upgrade as soon as possible when a new version is 
released. 

Update your `composer.json` file manually to require the latest version 
(avoid using the `dev-master`).

## Basic Usage

```php
<?php

require 'vendor/autoload.php';

use Rollerworks\Component\SplitToken\Argon2SplitTokenFactory;

// First, create the factory to generate a new SplitToken.
//
// Note: For unit testing it's highly recommended to use
// the FakeSplitTokenFactory instead as cryptographic operations
// can a little heavy.

$splitTokenFactory = new Argon2SplitTokenFactory();

// Step 1. Create a new SplitToken for usage

$token = $splitTokenFactory->create();

// The $authToken holds a \ParagonIE\HiddenString\HiddenString to prevent
// leakage of this value. You need to cast this object to an actual string
// at of usage.
//
// The $authToken is to be shared with the receiver (user) only.
// The value is already encoded as base64 uri-safe string.
//
//
// AGAIN, DO NOT STORE "THIS" VALUE IN THE DATABASE! Store the selector and verifier-hash instead.
// 
$authToken = $token->token(); // Returns a \ParagonIE\HiddenString\HiddenString object

// Indicate when the token must expire. Note that you need to clear the token from storage yourself.
// Pass null (or leave this method call absent) to never expire the token (not recommended).
//
$authToken->expiresAt(new \DateTimeImmutable('+1 hour'));

// Now to store the token cast the SplitToken to a SplitTokenValueHolder object.
//
// Unlike SplitToken this class is final and doesn't hold the full-token string.
// 
// Additionally you store the token with metadata (array only),
// See the linked manual below for more information.
$holder = $token->toValueHolder();

// Setting the token would look something like this.

// UPDATE site_user
// SET
//   recovery_selector = $holder->selector(),
//   recovery_verifier = $holder->verifierHash(),
//   recovery_expires_at = $holder->expiresAt(),
//   recovery_metadata = serialize($holder->metadata()),
//   recovery_timestamp = NOW()
// WHERE user_id = ...

// ----

// Step 2. Reconstruct the SplitToken from a user provided string.

// When the user provides the token verify if it's valid.
// This will throw an exception of token is not of the expected length.

$token = $splitTokenFactory->fromString($_GET['token']);

// $result = SELECT user_id, recover_verifier, recovery_expires_at, recovery_metadata WHERE recover_selector = $token->selector()
$holder = new SplitTokenValueHolder($token->selector(), $result['recovery_verifier'], $result['recovery_expires_at'], unserialize($result['recovery_metadata'], ['allowed_classes' => false]));

if ($token->matches($holder)) {
    echo 'OK, you have access';
} else {
    // Note: Make sure to remove the token from storage.
    
    echo 'NO, I cannot let you do this John.';
}
```

Once a result is found using the selector, the stored verifier-hash is used to 
compute a matching hash of the provided verifier. And the values are compared
in constant-time to protect against side-channel attacks.

**See also:**

* [Replacing an existing token](doc/replace-existing-token.md)
* [Using metadata for advanced usage](doc/using-metadata.md)
* [Configuring the hasher](doc/configuring-hasher.md)

## Error Handling

Because of security reasons, a `SplitToken` only throws generic runtime
exceptions for wrong usage, but no detailed exceptions about invalid input.

In the case of an error the memory allocation of the verifier and full token 
is zeroed to prevent leakage during a core dump or unhandled exception.

## Versioning

For transparency and insight into the release cycle, and for striving
to maintain backward compatibility, this package is maintained under
the Semantic Versioning guidelines as much as possible.

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

And constructed with the following guidelines:

* Breaking backward compatibility bumps the major (and resets the minor and patch)
* New additions without breaking backward compatibility bumps the minor (and resets the patch)
* Bug fixes and misc changes bumps the patch

For more information on SemVer, please visit <http://semver.org/>.

## Who is behind this library?

This library is brought to you by [Sebastiaan Stok](https://github.com/sstok).

The Split Token idea was first proposed by Paragon Initiative Enterprises.

## License

The Source Code of this package is subject to the terms of the
Mozilla Public License, version 2.0 ([MPLv2.0 License](LICENSE)).
