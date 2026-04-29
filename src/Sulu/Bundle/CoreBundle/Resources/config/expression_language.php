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

use Sulu\Bundle\CoreBundle\ExpressionLanguage\ContainerExpressionLanguageProvider;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_core.expression_language', ExpressionLanguage::class)
        ->args([
            null,
            [service('sulu_core.symfony_expression_language_provider')],
        ]);

    $services->set('sulu_core.symfony_expression_language_provider', ContainerExpressionLanguageProvider::class)
        ->args([service('service_container')]);
};
