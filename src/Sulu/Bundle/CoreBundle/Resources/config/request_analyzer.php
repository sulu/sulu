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

use Sulu\Component\Webspace\Analyzer\Attributes\AdminRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\DateTimeRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\ParameterRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\PortalInformationRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\SegmentRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\SystemRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\UrlRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\WebsiteRequestProcessor;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_core.webspace.request_analyzer', RequestAnalyzer::class)
        ->public()
        ->args([
            new Reference('request_stack'),
            tagged_iterator('sulu.request_attributes'),
        ]);

    $services->alias(RequestAnalyzerInterface::class, 'sulu_core.webspace.request_analyzer');

    $services->set('sulu_core.request_processor.parameter', ParameterRequestProcessor::class)
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            '%kernel.environment%',
        ])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu.request_attributes', ['priority' => 128]);

    $services->set('sulu_core.request_processor.url', UrlRequestProcessor::class)
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu.request_attributes', ['priority' => 64]);

    $services->set('sulu_core.request_processor.admin', AdminRequestProcessor::class)
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            '%kernel.environment%',
        ])
        ->tag('sulu.context', ['context' => 'admin'])
        ->tag('sulu.request_attributes', ['priority' => 0]);

    $services->set('sulu_core.request_processor.website', WebsiteRequestProcessor::class)
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            '%kernel.environment%',
        ])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu.request_attributes', ['priority' => 0]);

    $services->set('sulu_core.request_processor.portal_information', PortalInformationRequestProcessor::class)
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu.request_attributes', ['priority' => -128]);

    $services->set('sulu_core.request_processor.segment', SegmentRequestProcessor::class)
        ->args(['%sulu_website.segment_cookie_name%'])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu.request_attributes', ['priority' => -64]);

    $services->set('sulu_core.request_processor.system', SystemRequestProcessor::class)
        ->args([
            new Reference('sulu_security.system_store'),
            '%sulu.context%',
        ])
        ->tag('sulu.request_attributes', ['priority' => -32]);

    $services->set('sulu_core.request_processor.date_time', DateTimeRequestProcessor::class)
        ->tag('sulu.request_attributes', ['priority' => 0]);
};
