<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Symfony\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects services by tag and inject it via constructor argument.
 *
 * @internal
 *
 * @deprecated since version 2.6 use a tagged_iterator instead
 */
class TaggedServiceCollectorCompilerPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var string
     */
    private $tagName;

    /**
     * @var int
     */
    private $argumentNumber;

    /**
     * @var string
     */
    private $aliasAttribute;

    /**
     * @param string $serviceId
     * @param string $tagName
     * @param int $argumentNumber
     * @param string $aliasAttribute
     */
    public function __construct($serviceId, $tagName, $argumentNumber = 0, $aliasAttribute = null)
    {
        $this->serviceId = $serviceId;
        $this->tagName = $tagName;
        $this->argumentNumber = $argumentNumber;
        $this->aliasAttribute = $aliasAttribute;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->serviceId)) {
            return;
        }

        $references = [];
        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            foreach ($tags as $attributes) {
                $priority = \array_key_exists('priority', $attributes) ? $attributes['priority'] : 0;
                $reference = new Reference($id);
                if (!$this->aliasAttribute) {
                    $references[$priority][] = $reference;
                } elseif (\array_key_exists($this->aliasAttribute, $attributes)) {
                    $references[$priority][$attributes[$this->aliasAttribute]] = $reference;
                }
            }
        }

        if (0 === \count($references)) {
            return;
        }

        \krsort($references);
        $references = \call_user_func_array('array_merge', $references);

        $container->getDefinition($this->serviceId)->replaceArgument($this->argumentNumber, $references);
    }
}
