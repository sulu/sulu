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

use Sulu\Content\Application\ContentNormalizer\Normalizer\AuditableNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\AuthorNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\ContentHashNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\DimensionContentNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\ExcerptNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\LinkNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\RoutableNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\SecuredEntityNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\SeoNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\ShadowNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\TaxonomyNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\TemplateNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\WorkflowNormalizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_content.dimension_content_normalizer', DimensionContentNormalizer::class)
        ->tag('sulu_content.normalizer', ['priority' => 256]);

    $services->set('sulu_content.template_normalizer', TemplateNormalizer::class)
        ->tag('sulu_content.normalizer', ['priority' => 128]);

    $services->set('sulu_content.workflow_normalizer', WorkflowNormalizer::class)
        ->tag('sulu_content.normalizer', ['priority' => 64]);

    $services->set('sulu_content.taxonomy_normalizer', TaxonomyNormalizer::class)
        ->tag('sulu_content.normalizer', ['priority' => 32]);

    $services->set('sulu_content.excerpt_normalizer', ExcerptNormalizer::class)
        ->tag('sulu_content.normalizer', ['priority' => 31]);

    $services->set('sulu_content.seo_normalizer', SeoNormalizer::class)
        ->tag('sulu_content.normalizer', ['priority' => 30]);

    $services->set('sulu_content.routable_normalizer', RoutableNormalizer::class)
        ->args([new Reference('sulu_admin.metadata_provider_registry')])
        ->tag('sulu_content.normalizer', ['priority' => 16]);

    $services->set('sulu_content.author_normalizer', AuthorNormalizer::class)
        ->tag('sulu_content.normalizer', ['priority' => 8]);

    $services->set('sulu_content.link_normalizer', LinkNormalizer::class)
        ->tag('sulu_content.normalizer', ['priority' => 6]);

    $services->set('sulu_content.shadow_normalizer', ShadowNormalizer::class)
        ->tag('sulu_content.normalizer', ['priority' => 4]);

    $services->set('sulu_content.content_hash_normalizer', ContentHashNormalizer::class)
        ->args([new Reference('sulu_hash.auditable_hasher')])
        ->tag('sulu_content.normalizer', ['priority' => 3]);

    $services->set('sulu_content.auditable_normalizer', AuditableNormalizer::class)
        ->tag('sulu_content.normalizer', ['priority' => 2]);

    $services->set('sulu_content.secured_entity_normalizer', SecuredEntityNormalizer::class)
        ->args([
            new Reference('sulu_security.access_control_manager'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu_content.normalizer', ['priority' => 1]);
};
