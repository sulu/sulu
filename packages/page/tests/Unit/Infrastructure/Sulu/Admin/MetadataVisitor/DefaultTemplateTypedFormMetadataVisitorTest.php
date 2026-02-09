<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Admin\MetadataVisitor;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Infrastructure\Sulu\Admin\MetadataVisitor\DefaultTemplateTypedFormMetadataVisitor;

class DefaultTemplateTypedFormMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    private Webspace $webspace;

    private DefaultTemplateTypedFormMetadataVisitor $defaultTemplateTypedFormMetadataVisitor;

    public function setUp(): void
    {
        $this->webspace = new Webspace();

        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $webspaceManager->findWebspaceByKey(Argument::cetera())
            ->willReturn($this->webspace);

        $this->defaultTemplateTypedFormMetadataVisitor = new DefaultTemplateTypedFormMetadataVisitor(
            $webspaceManager->reveal(),
        );
    }

    public function testDefaultTemplateSet(): void
    {
        $this->webspace->addDefaultTemplate('page', 'homepage');

        $typedFormMetadata = new TypedFormMetadata();

        $this->defaultTemplateTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            'page',
            'en',
            ['webspace' => 'example'],
        );

        $this->assertSame('homepage', $typedFormMetadata->getDefaultType());
    }

    public function testDefaultTemplateNotSetForNoWebspace(): void
    {
        $this->webspace->addDefaultTemplate('page', 'homepage');

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->setDefaultType('default');

        $this->defaultTemplateTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            'page',
            'en',
        );

        $this->assertSame('default', $typedFormMetadata->getDefaultType());
    }

    public function testExcludedTemplateIsRemoved(): void
    {
        $this->webspace->addExcludedTemplate('overview');

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', new FormMetadata());
        $typedFormMetadata->addForm('overview', new FormMetadata());
        $typedFormMetadata->addForm('homepage', new FormMetadata());

        $this->defaultTemplateTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            'page',
            'en',
            ['webspace' => 'example'],
        );

        $forms = $typedFormMetadata->getForms();
        $this->assertArrayHasKey('default', $forms);
        $this->assertArrayNotHasKey('overview', $forms);
        $this->assertArrayHasKey('homepage', $forms);
    }

    public function testMultipleExcludedTemplatesAreRemoved(): void
    {
        $this->webspace->addExcludedTemplate('overview');
        $this->webspace->addExcludedTemplate('default-2');

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', new FormMetadata());
        $typedFormMetadata->addForm('default-2', new FormMetadata());
        $typedFormMetadata->addForm('overview', new FormMetadata());
        $typedFormMetadata->addForm('homepage', new FormMetadata());

        $this->defaultTemplateTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            'page',
            'en',
            ['webspace' => 'example'],
        );

        $forms = $typedFormMetadata->getForms();
        $this->assertArrayHasKey('default', $forms);
        $this->assertArrayNotHasKey('default-2', $forms);
        $this->assertArrayNotHasKey('overview', $forms);
        $this->assertArrayHasKey('homepage', $forms);
    }

    public function testExcludedTemplatesNotRemovedWhenNoWebspace(): void
    {
        $this->webspace->addExcludedTemplate('overview');

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('default', new FormMetadata());
        $typedFormMetadata->addForm('overview', new FormMetadata());

        $this->defaultTemplateTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            'page',
            'en',
        );

        $forms = $typedFormMetadata->getForms();
        $this->assertArrayHasKey('default', $forms);
        $this->assertArrayHasKey('overview', $forms, 'Template should not be removed when no webspace is provided');
    }
}
