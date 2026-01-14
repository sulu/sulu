<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\CacheLifetimeMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateMetadata;

class TemplateMetadataTest extends TestCase
{
    public function testMergeOverridesAllValues(): void
    {
        $template = new TemplateMetadata(
            'App\Controller\OriginalController::indexAction',
            '@Original/view',
            new CacheLifetimeMetadata('seconds', '3600')
        );

        $otherTemplate = new TemplateMetadata(
            'App\Controller\OverrideController::indexAction',
            '@Override/view',
            new CacheLifetimeMetadata('seconds', '7200')
        );

        $merged = $template->merge($otherTemplate);

        $this->assertNotSame($template, $merged);
        $this->assertNotSame($otherTemplate, $merged);
        $this->assertSame('App\Controller\OverrideController::indexAction', $merged->getController());
        $this->assertSame('@Override/view', $merged->getView());
        $this->assertSame('7200', $merged->getCacheLifetime()?->getValue());
    }

    public function testMergeFallsBackToOriginalValues(): void
    {
        $template = new TemplateMetadata(
            'App\Controller\OriginalController::indexAction',
            '@Original/view',
            new CacheLifetimeMetadata('seconds', '3600')
        );

        $otherTemplate = new TemplateMetadata(null, null, null);

        $merged = $template->merge($otherTemplate);

        $this->assertNotSame($template, $merged);
        $this->assertSame('App\Controller\OriginalController::indexAction', $merged->getController());
        $this->assertSame('@Original/view', $merged->getView());
        $this->assertSame('3600', $merged->getCacheLifetime()?->getValue());
    }

    public function testMergePartialOverride(): void
    {
        $template = new TemplateMetadata(
            'App\Controller\OriginalController::indexAction',
            '@Original/view',
            new CacheLifetimeMetadata('seconds', '3600')
        );

        $otherTemplate = new TemplateMetadata(
            'App\Controller\OverrideController::indexAction',
            null,
            null
        );

        $merged = $template->merge($otherTemplate);

        $this->assertSame('App\Controller\OverrideController::indexAction', $merged->getController());
        $this->assertSame('@Original/view', $merged->getView());
        $this->assertSame('3600', $merged->getCacheLifetime()?->getValue());
    }

    public function testMergePartialEmptyStringOverride(): void
    {
        $template = new TemplateMetadata(
            'App\Controller\OriginalController::indexAction',
            '@Original/view',
            new CacheLifetimeMetadata('seconds', '3600')
        );

        $otherTemplate = new TemplateMetadata(
            'App\Controller\OverrideController::indexAction',
            '',
            null
        );

        $merged = $template->merge($otherTemplate);

        $this->assertSame('App\Controller\OverrideController::indexAction', $merged->getController());
        $this->assertSame('', $merged->getView());
        $this->assertSame('3600', $merged->getCacheLifetime()?->getValue());
    }

    public function testMergeCreatesNewInstance(): void
    {
        $template = new TemplateMetadata(
            'App\Controller\TestController::indexAction',
            '@Test/view',
            null
        );

        $otherTemplate = new TemplateMetadata(
            'App\Controller\TestController::indexAction',
            '@Test/view',
            null
        );

        $merged = $template->merge($otherTemplate);

        $this->assertNotSame($template, $merged);
        $this->assertNotSame($otherTemplate, $merged);
    }
}
