<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('sulu_tag.tag_selection_property_resolver', \Sulu\Bundle\TagBundle\Infrastructure\Sulu\Content\PropertyResolver\TagSelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_tag.tag_resource_loader', \Sulu\Bundle\TagBundle\Infrastructure\Sulu\Content\ResourceLoader\TagResourceLoader::class)
        ->args([service('sulu.repository.tag')])
        ->tag('sulu_content.resource_loader', ['key' => 'tag']);
};
