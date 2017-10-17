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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddArgumentCompilerPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var mixed
     */
    private $argument;

    public function __construct($serviceId, $argument)
    {
        $this->serviceId = $serviceId;
        $this->argument = $argument;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->serviceId)) {
            return;
        }

        $container->getDefinition($this->serviceId)
            ->addArgument($this->argument);
    }
}
