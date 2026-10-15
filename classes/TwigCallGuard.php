<?php

namespace Renatio\DynamicPDF\Classes;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Node\Expression\CallExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\Sandbox\SecurityError;

/**
 * Rejects denied calls while a template is parsed, so saving it already fails.
 */
class TwigCallGuard extends AbstractExtension implements NodeVisitorInterface
{
    protected TwigDenylist $denylist;

    public function __construct()
    {
        $this->denylist = new TwigDenylist;
    }

    /**
     * @return array<int, NodeVisitorInterface>
     */
    public function getNodeVisitors(): array
    {
        return [$this];
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof CallExpression && $node->hasAttribute('twig_callable') && $this->denylist->deniesCall($node->getAttribute('twig_callable'))) {
            $this->deny(sprintf('The "%s" %s', $node->getAttribute('name'), $node->getAttribute('type')), $node);
        }

        if ($this->denylist->deniesTag($node->getNodeTag())) {
            $this->deny(sprintf('The "%s" tag', $node->getNodeTag()), $node);
        }

        if (! $node instanceof GetAttrExpression || $node instanceof GuardedGetAttrNode) {
            return $node;
        }

        return $this->guard($node);
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    /**
     * Runs after October's GetAttrAdjuster has swapped in its own node.
     */
    public function getPriority(): int
    {
        return 10;
    }

    /**
     * Optimized array access compiles to an inline offsetGet, which would bypass the guard.
     */
    protected function guard(GetAttrExpression $node): GuardedGetAttrNode
    {
        $nodes = ['node' => $node->getNode('node'), 'attribute' => $node->getNode('attribute')];

        if ($node->hasNode('arguments')) {
            $nodes['arguments'] = $node->getNode('arguments');
        }

        $guarded = new GuardedGetAttrNode($nodes, [
            'type' => $node->getAttribute('type'),
            'is_defined_test' => $node->isDefinedTestEnabled(),
            'ignore_strict_check' => $node->getAttribute('ignore_strict_check'),
            'optimizable' => false,
        ], $node->getTemplateLine());

        if ($node->isDefinedTestEnabled()) {
            $guarded->enableDefinedTest();
        }

        return $guarded;
    }

    protected function deny(string $subject, Node $node): never
    {
        throw new SecurityError("{$subject} is not allowed in PDF templates.", $node->getTemplateLine(), $node->getSourceContext());
    }
}
