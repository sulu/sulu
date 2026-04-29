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

use Sulu\Component\Rest\Csv\CsvHandler;
use Sulu\Component\Rest\DoctrineRestHelper;
use Sulu\Component\Rest\FlattenExceptionNormalizer;
use Sulu\Component\Rest\Handler\DateHandler;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Filter\BooleanFilterType;
use Sulu\Component\Rest\ListBuilder\Filter\DateFilterType;
use Sulu\Component\Rest\ListBuilder\Filter\DateTimeFilterType;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeRegistry;
use Sulu\Component\Rest\ListBuilder\Filter\NumberFilterType;
use Sulu\Component\Rest\ListBuilder\Filter\SelectFilterType;
use Sulu\Component\Rest\ListBuilder\Filter\SelectionFilterType;
use Sulu\Component\Rest\ListBuilder\Filter\TextFilterType;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\RestHelper;
use Sulu\Component\Rest\RestHelperInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_core.rest_helper', RestHelper::class)
        ->args([new Reference('sulu_core.list_rest_helper')]);

    $services->alias(RestHelperInterface::class, 'sulu_core.rest_helper');

    $services->set('sulu_core.doctrine_rest_helper', DoctrineRestHelper::class)
        ->public()
        ->args([new Reference('sulu_core.list_rest_helper')]);

    $services->set('sulu_core.list_rest_helper', ListRestHelper::class)
        ->public()
        ->args([new Reference('request_stack')]);

    $services->alias(ListRestHelperInterface::class, 'sulu_core.list_rest_helper');

    $services->set('sulu_core.doctrine_list_builder_factory', DoctrineListBuilderFactory::class)
        ->public()
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_core.list_builder_filter_type_registry'),
            new Reference('event_dispatcher'),
            '%sulu_security.permissions%',
            new Reference('sulu_security.access_control_query_enhancer'),
        ]);

    $services->alias(DoctrineListBuilderFactoryInterface::class, 'sulu_core.doctrine_list_builder_factory');

    $services->set('sulu_core.list_builder_filter_type_registry', FilterTypeRegistry::class)
        ->args([tagged_iterator('sulu_core.list_builder_filter_type', indexAttribute: 'alias', defaultIndexMethod: 'getDefaultIndexName')]);

    $services->set('sulu_core.text_filter_type', TextFilterType::class)
        ->tag('sulu_core.list_builder_filter_type');

    $services->set('sulu_core.boolean_filter_type', BooleanFilterType::class)
        ->tag('sulu_core.list_builder_filter_type');

    $services->set('sulu_core.number_filter_type', NumberFilterType::class)
        ->tag('sulu_core.list_builder_filter_type');

    $services->set('sulu_core.select_filter_type', SelectFilterType::class)
        ->tag('sulu_core.list_builder_filter_type');

    $services->set('sulu_core.selection_filter_type', SelectionFilterType::class)
        ->tag('sulu_core.list_builder_filter_type');

    $services->set('sulu_core.date_filter_type', DateFilterType::class)
        ->tag('sulu_core.list_builder_filter_type');

    $services->set('sulu_core.date_time_filter_type', DateTimeFilterType::class)
        ->tag('sulu_core.list_builder_filter_type');

    $services->set('sulu_core.rest.datetime_handler', DateHandler::class)
        ->tag('jms_serializer.subscribing_handler');

    $services->set('sulu_core.rest.view_handler.csv', CsvHandler::class)
        ->args([new Reference('sulu_core.array_serializer')]);

    $services->set('sulu_core.rest.flatten_exception_normalizer', FlattenExceptionNormalizer::class)
        ->decorate('fos_rest.serializer.flatten_exception_normalizer')
        ->args([
            new Reference('sulu_core.rest.flatten_exception_normalizer.inner'),
            new Reference('translator'),
        ]);
};
