<?php

use Pest\Rector\Set\PestSetList;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\MethodCall\RemoveReflectionSetAccessibleCallsRector;
use Rector\ValueObject\PhpVersion;

/**
 * declare(strict_types=1) is deliberately left out: October hands models loosely typed values
 * from the database, which strict mode would turn into runtime TypeErrors. Readonly promotion
 * is left out too, since it breaks the Mockery-based tests.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/classes',
        __DIR__ . '/console',
        __DIR__ . '/controllers',
        __DIR__ . '/models',
        __DIR__ . '/tests',
        __DIR__ . '/Plugin.php',
    ])
    ->withSkip([
        __DIR__ . '/controllers/layouts',
        __DIR__ . '/controllers/templates',
    ])
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withSets([PestSetList::CODING_STYLE])
    ->withRules([
        RemoveReflectionSetAccessibleCallsRector::class,
        InlineConstructorDefaultToPropertyRector::class,
    ]);
