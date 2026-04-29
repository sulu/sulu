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

use Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Content\PropertyResolver\CategorySelectionPropertyResolver;
use Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Content\PropertyResolver\SingleCategorySelectionPropertyResolver;
use Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Content\ResourceLoader\CategoryResourceLoader;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_category.category_selection_property_resolver', CategorySelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_category.single_category_selection_property_resolver', SingleCategorySelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_category.category_resource_loader', CategoryResourceLoader::class)
        ->args([service('sulu_category.category_manager')])
        ->tag('sulu_content.resource_loader', ['key' => 'category']);
};
