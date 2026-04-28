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

use DeviceDetector\DeviceDetector;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Admin\AudienceTargetingAdmin;
use Sulu\Bundle\AudienceTargetingBundle\Admin\Helper\WebspaceSelect;
use Sulu\Bundle\AudienceTargetingBundle\Controller\TargetGroupController;
use Sulu\Bundle\AudienceTargetingBundle\Controller\TargetGroupEvaluationController;
use Sulu\Bundle\AudienceTargetingBundle\EventListener\DeviceDetectorSubscriber;
use Sulu\Bundle\AudienceTargetingBundle\EventListener\TargetGroupSubscriber;
use Sulu\Bundle\AudienceTargetingBundle\Infrastructure\Sulu\Admin\MetadataVisitor\BlockSettingsFormMetadataVisitor;
use Sulu\Bundle\AudienceTargetingBundle\Infrastructure\Sulu\Content\PropertyResolver\BlockVisitor\TargetGroupBlockVisitor;
use Sulu\Bundle\AudienceTargetingBundle\Request\ForwardedUrlRequestProcessor;
use Sulu\Bundle\AudienceTargetingBundle\Rule\BrowserRule;
use Sulu\Bundle\AudienceTargetingBundle\Rule\DeviceTypeRule;
use Sulu\Bundle\AudienceTargetingBundle\Rule\LocaleRule;
use Sulu\Bundle\AudienceTargetingBundle\Rule\OperatingSystemRule;
use Sulu\Bundle\AudienceTargetingBundle\Rule\QueryStringRule;
use Sulu\Bundle\AudienceTargetingBundle\Rule\ReferrerRule;
use Sulu\Bundle\AudienceTargetingBundle\Rule\RuleCollection;
use Sulu\Bundle\AudienceTargetingBundle\Serializer\Subscriber\TargetGroupRuleSerializeSubscriber;
use Sulu\Bundle\AudienceTargetingBundle\Serializer\Subscriber\TargetGroupSerializeSubscriber;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupEvaluator;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupFactory;
use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStore;
use Sulu\Bundle\AudienceTargetingBundle\Visitor\AudienceTargetingSmartContentFiltersVisitor;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_audience_targeting.admin', AudienceTargetingAdmin::class)
        ->args([
            new Reference(ViewBuilderFactoryInterface::class),
            new Reference('sulu_audience_targeting.rules_collection'),
            new Reference('sulu_security.security_checker'),
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_audience_targeting.webspace_select_helper', WebspaceSelect::class)
        ->public()
        ->args([new Reference('sulu_core.webspace.webspace_manager')]);

    $services->set('sulu_audience_targeting.target_group_controller', TargetGroupController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('sulu.repository.target_group'),
            new Reference('sulu.repository.target_group_rule'),
            new Reference('sulu.repository.target_group_condition'),
            new Reference('sulu.repository.target_group_webspace'),
            new Reference('doctrine.orm.entity_manager'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_audience_targeting.serializer.target_group_subscriber', TargetGroupSerializeSubscriber::class)
        ->tag('jms_serializer.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_audience_targeting.serializer.target_group_rule_serializer', TargetGroupRuleSerializeSubscriber::class)
        ->tag('jms_serializer.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_audience_targeting.request_processor.forwarded_url', ForwardedUrlRequestProcessor::class)
        ->args(['%sulu_audience_targeting.headers.url%'])
        ->tag('sulu.request_attributes', ['priority' => 96])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_audience_targeting.device_detector', DeviceDetector::class);

    $services->set('sulu_audience_targeting.device_detector_subscriber', DeviceDetectorSubscriber::class)
        ->args([new Reference('sulu_audience_targeting.device_detector')])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_audience_targeting.target_group_evaluator', TargetGroupEvaluator::class)
        ->args([
            new Reference('sulu_audience_targeting.rules_collection'),
            new Reference('sulu.repository.target_group'),
            new Reference('sulu_core.webspace.request_analyzer'),
        ]);

    $services->set('sulu_audience_targeting.rules_collection', RuleCollection::class)
        ->args([tagged_iterator('sulu.audience_target_rule', indexAttribute: 'alias')]);

    $services->set('sulu_audience_targeting.rules.locale', LocaleRule::class)
        ->args([
            new Reference('request_stack'),
            new Reference('translator'),
        ])
        ->tag('sulu.audience_target_rule', ['alias' => 'locale']);

    $services->set('sulu_audience_targeting.rules.referrer', ReferrerRule::class)
        ->args([
            new Reference('request_stack'),
            new Reference('translator'),
            '%sulu_audience_targeting.hit.headers.referrer%',
        ])
        ->tag('sulu.audience_target_rule', ['alias' => 'referrer']);

    $services->set('sulu_audience_targeting.rules.query_string', QueryStringRule::class)
        ->args([
            new Reference('request_stack'),
            new Reference('translator'),
            '%sulu_audience_targeting.headers.url%',
        ])
        ->tag('sulu.audience_target_rule', ['alias' => 'query_string']);

    $services->set('sulu_audience_targeting.rules.browser', BrowserRule::class)
        ->args([
            new Reference('sulu_audience_targeting.device_detector'),
            new Reference('translator'),
        ])
        ->tag('sulu.audience_target_rule', ['alias' => 'browser']);

    $services->set('sulu_audience_targeting.rules.os', OperatingSystemRule::class)
        ->args([
            new Reference('sulu_audience_targeting.device_detector'),
            new Reference('translator'),
        ])
        ->tag('sulu.audience_target_rule', ['alias' => 'os']);

    $services->set('sulu_audience_targeting.rules.device', DeviceTypeRule::class)
        ->args([
            new Reference('sulu_audience_targeting.device_detector'),
            new Reference('translator'),
        ])
        ->tag('sulu.audience_target_rule', ['alias' => 'device']);

    $services->set('sulu_audience_targeting.target_group_evaluation_controller', TargetGroupEvaluationController::class)
        ->public()
        ->args([
            new Reference('sulu_audience_targeting.target_group_evaluator'),
            new Reference('sulu.repository.target_group'),
            new Reference('sulu_audience_targeting.target_group_store'),
            '%sulu_audience_targeting.headers.target_group%',
        ]);

    $services->set('sulu_audience_targeting.target_group_subscriber', TargetGroupSubscriber::class)
        ->args([
            new Reference('twig'),
            expr('container.hasParameter(\'sulu.preview\') ? parameter(\'sulu.preview\') : false'),
            new Reference('sulu_audience_targeting.target_group_store'),
            new Reference('sulu_audience_targeting.target_group_evaluator'),
            new Reference('sulu.repository.target_group'),
            '%sulu_audience_targeting.url%',
            '%sulu_audience_targeting.hit.url%',
            '%sulu_audience_targeting.headers.url%',
            '%sulu_audience_targeting.hit.headers.referrer%',
            '%sulu_audience_targeting.hit.headers.uuid%',
            '%sulu_audience_targeting.headers.target_group%',
            '%sulu_audience_targeting.cookies.target_group%',
            '%sulu_audience_targeting.cookies.session%',
        ])
        ->tag('kernel.event_subscriber')
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_audience_targeting.target_group_store', TargetGroupStore::class)
        ->public();

    $services->set('sulu_audience_targeting.target_group_block_visitor', TargetGroupBlockVisitor::class)
        ->args([new Reference('sulu_audience_targeting.target_group_store')])
        ->tag('sulu_content.block_visitor');

    $services->set('sulu_audience_targeting.audience_targeting_smart_content_filters_visitor', AudienceTargetingSmartContentFiltersVisitor::class)
        ->args([new Reference('sulu_audience_targeting.target_group_store')])
        ->tag('sulu_content.smart_content_filters_visitor');

    $services->set('sulu_audience_targeting.target_group_factory', TargetGroupFactory::class)
        ->args([new Reference('doctrine.orm.entity_manager')]);

    $services->set('sulu_audience_targeting.block_settings_form_metadata_visitor', BlockSettingsFormMetadataVisitor::class)
        ->args([new Reference('sulu_admin.xml_form_metadata_loader')])
        ->tag('sulu_admin.form_metadata_visitor');
};
