<?php

$header = <<<EOF
This Source Code Form is subject to the terms of the Mozilla Public
License, v. 2.0. If a copy of the MPL was not distributed with this
file, You can obtain one at http://mozilla.org/MPL/2.0/.
EOF;

/** @var \Symfony\Component\Finder\Finder $finder */
$finder = PhpCsFixer\Finder::create();
$finder
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules(
        array_merge(
            require __DIR__ . '/vendor/rollerscapes/standards/php-cs-fixer-rules.php',
            ['header_comment' => ['header' => $header]])
    )
    ->setFinder($finder);

return $config;
