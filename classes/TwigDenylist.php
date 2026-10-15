<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Support\Str;
use Twig\AbstractTwigCallable;

/**
 * October's SecurityPolicy checks no tag, function or filter.
 */
class TwigDenylist
{
    protected const FUNCTIONS = [
        'config' => true, 'env' => true, 'response' => true, 'redirect' => true, 'abort' => true,
        'ajaxhandler' => true, 'flash' => true, 'dump' => true, 'd' => true, 'dd' => true,
    ];

    /**
     * Flash reading purges the session's messages; cache writes into the theme's own fragment cache.
     */
    protected const TAGS = ['flash' => true, 'cache' => true];

    protected const FUNCTION_PATTERN = '/(^|_)(macro|mixin)s?(_|$)|^form_/';

    protected const MACRO_METHODS = ['macro' => true, 'mixin' => true, 'flushmacros' => true, 'macrocall' => true];

    protected const STATIC_METHODS = [
        '__call' => true, '__callstatic' => true, 'swap' => true, 'spy' => true, 'partialmock' => true,
        'shouldreceive' => true, 'shouldnotreceive' => true, 'expects' => true, 'resolved' => true,
        'getfacaderoot' => true, 'getfacadeapplication' => true, 'setfacadeapplication' => true,
        'clearresolvedinstance' => true, 'clearresolvedinstances' => true, 'defaultaliases' => true, 'defaults' => true,
        'component' => true, 'token' => true, 'signedroute' => true, 'temporarysignedroute' => true,
    ];

    protected const STATIC_METHOD_PATTERN = '/using|^(set|force|flush|freeze|forget|register|extend|use)/';

    public function deniesTag(?string $tag): bool
    {
        return isset(self::TAGS[strtolower((string) $tag)]);
    }

    public function deniesCall(AbstractTwigCallable $callable): bool
    {
        $name = strtolower($callable->getName());

        if (isset(self::FUNCTIONS[$name]) || preg_match(self::FUNCTION_PATTERN, $name)) {
            return true;
        }

        if ($callable->getDynamicName() === $callable->getName()) {
            return false;
        }

        foreach ($callable->getArguments() as $wildcard) {
            $method = strtolower(Str::camel((string) $wildcard));

            if (isset(self::MACRO_METHODS[$method]) || isset(self::STATIC_METHODS[$method]) || preg_match(self::STATIC_METHOD_PATTERN, $method)) {
                return true;
            }
        }

        return false;
    }
}
