<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Symfony\CompilerPass\Customizer;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AddArgumentCustomizer implements CustomizerInterface
{
    /**
     * @var mixed
     */
    private $argument;

    public function __construct($argument)
    {
        $this->argument = $argument;
    }

    public function customize(Definition $definition, ContainerBuilder $container): void
    {
        $definition->addArgument($this->argument);
    }
}
