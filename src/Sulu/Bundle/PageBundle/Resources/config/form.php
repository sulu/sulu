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

use Sulu\Bundle\PageBundle\Form\Type\HomeDocumentType;
use Sulu\Bundle\PageBundle\Form\Type\PageDocumentType;
use Sulu\Component\Content\Form\Type\DocumentObjectType;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('dtl_content.form.factory.document_type', DocumentObjectType::class)
        ->args([new Reference('sulu_document_manager.document_manager')])
        ->tag('form.type');

    $services->set('dtl_content.form.type.page', PageDocumentType::class)
        ->args([
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.metadata_factory'),
        ])
        ->tag('form.type');

    $services->set('dtl_content.form.type.home', HomeDocumentType::class)
        ->tag('form.type');
};
