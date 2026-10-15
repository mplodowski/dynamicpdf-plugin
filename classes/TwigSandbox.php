<?php

namespace Renatio\DynamicPDF\Classes;

use System\Twig\SecurityPolicy;
use Twig\Cache\FilesystemCache;
use Twig\Environment;
use Twig\Extension\SandboxExtension;

/**
 * Only October's own SecurityPolicy makes its GetAttrNode swap collections for safe proxies,
 * so it also replaces the legacy policy safe mode adds with cms.security_policy_v1.
 */
class TwigSandbox
{
    public function enabled(): bool
    {
        return (bool) config('renatio.dynamicpdf.sandbox', true);
    }

    public function apply(Environment $twig): Environment
    {
        if (! $this->enabled()) {
            return $twig;
        }

        if ($twig->hasExtension(SandboxExtension::class)) {
            $sandbox = $twig->getExtension(SandboxExtension::class);
            $sandbox->setSecurityPolicy(new SecurityPolicy);
            $sandbox->enableSandbox();
        } else {
            $twig->addExtension(new SandboxExtension(new SecurityPolicy, true));
        }

        $twig->addExtension(new TwigCallGuard);

        if ($twig->getCache(false) instanceof FilesystemCache) {
            $twig->setCache(new FilesystemCache(
                storage_path('cms/twig/dynamicpdf-' . $this->signature()),
                config('cms.force_bytecode_invalidation') ? FilesystemCache::FORCE_BYTECODE_INVALIDATION : 0,
            ));
        }

        return $twig;
    }

    /**
     * Changes with the guard code, so templates compiled under older rules are not reused.
     */
    protected function signature(): string
    {
        $context = hash_init('xxh128');

        foreach (['TwigCallGuard', 'GuardedGetAttrNode', 'TwigDenylist'] as $class) {
            hash_update_file($context, __DIR__ . "/{$class}.php");
        }

        return substr(hash_final($context), 0, 12);
    }
}
