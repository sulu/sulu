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

class ReplaceClassCustomizer implements CustomizerInterface
{
    /**
     * @var string
     */
    private $customClass;

    public function __construct(string $customClass)
    {
        $this->customClass = $customClass;
    }

    public function customize(Definition $definition, ContainerBuilder $container)
    {
        $definition->setClass($this->customClass);
    }
}
