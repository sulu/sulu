<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Symfony\CompilerPass;

use Sulu\Component\Symfony\CompilerPass\Customizer\CustomizerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceCustomizerCompilerPass implements CompilerPassInterface
{
    public static function customize(string $serviceId): self
    {
        return new self($serviceId);
    }

    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var CustomizerInterface[]
     */
    private $customizer;

    public function __construct(string $serviceId)
    {
        $this->serviceId = $serviceId;
    }

    public function with(CustomizerInterface $customizer)
    {
        $this->customizer[] = $customizer;

        return $this;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->serviceId)) {
            return;
        }

        $definition = $container->getDefinition($this->serviceId);
        foreach ($this->customizer as $customizer) {
            $customizer->customize($definition, $container);
        }
    }
}
