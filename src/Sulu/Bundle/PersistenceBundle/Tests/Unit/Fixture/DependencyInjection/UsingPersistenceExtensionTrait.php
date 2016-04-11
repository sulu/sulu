<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\Tests\Unit\Fixture\DependencyInjection;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class UsingPersistenceExtensionTrait extends Extension
{
    use PersistenceExtensionTrait;

    public function load(array $config, ContainerBuilder $container)
    {
        $this->configurePersistence($config['objects'], $container);
    }
}
