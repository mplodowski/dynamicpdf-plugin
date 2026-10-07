<?php

use Pest\Rector\Rules\SimplifyToBeTruthyFalsyRector;
use Pest\Rector\Rules\SimplifyToLiteralBooleanRector;
use Pest\Rector\Set\PestSetList;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\MethodCall\RemoveReflectionSetAccessibleCallsRector;
use Rector\ValueObject\PhpVersion;

/**
 * declare(strict_types=1) is deliberately left out: October hands models loosely typed values
 * from the database, which strict mode would turn into runtime TypeErrors.
 *
 * SimplifyToLiteralBooleanRector and SimplifyToBeTruthyFalsyRector are skipped because they turn strict
 * expectations into toBeEmpty()/toBeFalsy(), which also pass for null, '' and 0.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/classes',
        __DIR__ . '/console',
        __DIR__ . '/controllers',
        __DIR__ . '/listeners',
        __DIR__ . '/models',
        __DIR__ . '/traits',
        __DIR__ . '/tests',
        __DIR__ . '/Plugin.php',
    ])
    ->withSkip([
        __DIR__ . '/controllers/layouts',
        __DIR__ . '/controllers/templates',
        SimplifyToLiteralBooleanRector::class,
        SimplifyToBeTruthyFalsyRector::class,
    ])
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withSets([PestSetList::CODING_STYLE])
    ->withRules([
        RemoveReflectionSetAccessibleCallsRector::class,
        InlineConstructorDefaultToPropertyRector::class,
    ]);
