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

use Sulu\Content\Application\ContentMerger\Merger\AuditableMerger;
use Sulu\Content\Application\ContentMerger\Merger\AuthorMerger;
use Sulu\Content\Application\ContentMerger\Merger\DimensionContentMerger;
use Sulu\Content\Application\ContentMerger\Merger\ExcerptMerger;
use Sulu\Content\Application\ContentMerger\Merger\LinkMerger;
use Sulu\Content\Application\ContentMerger\Merger\RoutableMerger;
use Sulu\Content\Application\ContentMerger\Merger\SeoMerger;
use Sulu\Content\Application\ContentMerger\Merger\ShadowMerger;
use Sulu\Content\Application\ContentMerger\Merger\TaxonomyMerger;
use Sulu\Content\Application\ContentMerger\Merger\TemplateMerger;
use Sulu\Content\Application\ContentMerger\Merger\WebspaceMerger;
use Sulu\Content\Application\ContentMerger\Merger\WorkflowMerger;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_content.routable_merger', RoutableMerger::class)
        ->tag('sulu_content.merger', ['priority' => 512]);

    $services->set('sulu_content.dimension_content_merger', DimensionContentMerger::class)
        ->tag('sulu_content.merger', ['priority' => 256]);

    $services->set('sulu_content.template_merger', TemplateMerger::class)
        ->tag('sulu_content.merger', ['priority' => 128]);

    $services->set('sulu_content.workflow_merger', WorkflowMerger::class)
        ->tag('sulu_content.merger', ['priority' => 64]);

    $services->set('sulu_content.excerpt_merger', ExcerptMerger::class)
        ->tag('sulu_content.merger', ['priority' => 32]);

    $services->set('sulu_content.taxonomy_merger', TaxonomyMerger::class)
        ->tag('sulu_content.merger', ['priority' => 31]);

    $services->set('sulu_content.seo_merger', SeoMerger::class)
        ->tag('sulu_content.merger', ['priority' => 24]);

    $services->set('sulu_content.webspace_merger', WebspaceMerger::class)
        ->tag('sulu_content.merger', ['priority' => 16]);

    $services->set('sulu_content.author_merger', AuthorMerger::class)
        ->tag('sulu_content.merger', ['priority' => 8]);

    $services->set('sulu_content.shadow_merger', ShadowMerger::class)
        ->tag('sulu_content.merger', ['priority' => 4]);

    $services->set('sulu_content.link_merger', LinkMerger::class)
        ->tag('sulu_content.merger', ['priority' => 3]);

    $services->set('sulu_content.auditable_merger', AuditableMerger::class)
        ->tag('sulu_content.merger', ['priority' => 2]);
};
