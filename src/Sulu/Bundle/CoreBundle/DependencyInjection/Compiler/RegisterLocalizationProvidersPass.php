<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds tagged services to the LocalizationManager.
 */
class RegisterLocalizationProvidersPass implements CompilerPassInterface
{
    const LOCALIZATION_PROVIDER_TAG = 'sulu.localization_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $localizationManager = $container->getDefinition('sulu.core.localization_manager');

        $taggedServices = $container->findTaggedServiceIds(self::LOCALIZATION_PROVIDER_TAG);

        foreach ($taggedServices as $id => $attributes) {
            $localizationProvider = $container->getDefinition($id);
            $localizationManager->addMethodCall('addLocalizationProvider', [$localizationProvider]);
        }
    }
}
