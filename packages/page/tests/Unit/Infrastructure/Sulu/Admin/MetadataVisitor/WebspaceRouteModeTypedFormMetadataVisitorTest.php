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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\MetadataVisitor\WebspaceRouteModeTypedFormMetadataVisitor;

class WebspaceRouteModeTypedFormMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    private Webspace $webspace;

    private WebspaceRouteModeTypedFormMetadataVisitor $webspaceRouteModeTypedFormMetadataVisitor;

    public function setUp(): void
    {
        $this->webspace = new Webspace();

        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $webspaceManager->findWebspaceByKey(Argument::cetera())
            ->willReturn($this->webspace);

        $this->webspaceRouteModeTypedFormMetadataVisitor = new WebspaceRouteModeTypedFormMetadataVisitor(
            $webspaceManager->reveal(),
        );
    }

    public function testRoutePropertyModeSet(): void
    {
        $this->webspace->setResourceLocatorStrategy('tree_leaf_edit');

        $routeMetadata = $this->createRouteFieldMetadata();
        $formMetadata = $this->createFormMetadata('default', [$routeMetadata]);
        $typedFormMetadata = $this->createTypedFormMetadata([$formMetadata]);

        $this->webspaceRouteModeTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            PageInterface::TEMPLATE_TYPE,
            'en',
            ['webspace' => 'example'],
        );

        $this->assertSame('tree_leaf_edit', $this->getRouteFieldMetadataModeParam($routeMetadata));
    }

    public function testRoutePropertyModeSetWithOtherFields(): void
    {
        $this->webspace->setResourceLocatorStrategy('tree_leaf_edit');

        $textMetadata = $this->createFieldMetadata('title', 'text_line');
        $routeMetadata = $this->createRouteFieldMetadata();
        $formMetadata = $this->createFormMetadata('default', [$textMetadata, $routeMetadata]);
        $typedFormMetadata = $this->createTypedFormMetadata([$formMetadata]);

        $this->webspaceRouteModeTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            PageInterface::TEMPLATE_TYPE,
            'en',
            ['webspace' => 'example'],
        );

        $this->assertNull($this->getRouteFieldMetadataModeParam($textMetadata));
        $this->assertSame('tree_leaf_edit', $this->getRouteFieldMetadataModeParam($routeMetadata));
    }

    public function testRoutePropertyModeSetInSection(): void
    {
        $this->webspace->setResourceLocatorStrategy('tree_leaf_edit');

        $routeMetadata = $this->createRouteFieldMetadata();
        $sectionMetadata = $this->createSectionMetadata([$routeMetadata]);
        $formMetadata = $this->createFormMetadata('default', [$sectionMetadata]);
        $typedFormMetadata = $this->createTypedFormMetadata([$formMetadata]);

        $this->webspaceRouteModeTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            PageInterface::TEMPLATE_TYPE,
            'en',
            ['webspace' => 'example'],
        );

        $this->assertSame('tree_leaf_edit', $this->getRouteFieldMetadataModeParam($routeMetadata));
    }

    public function testRoutePropertyModeSetInNestedSection(): void
    {
        $this->webspace->setResourceLocatorStrategy('tree_leaf_edit');

        $routeMetadata = $this->createRouteFieldMetadata();
        $nestedSectionMetadata = $this->createSectionMetadata([$routeMetadata]);
        $sectionMetadata = $this->createSectionMetadata([$nestedSectionMetadata]);
        $formMetadata = $this->createFormMetadata('default', [$sectionMetadata]);
        $typedFormMetadata = $this->createTypedFormMetadata([$formMetadata]);

        $this->webspaceRouteModeTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            PageInterface::TEMPLATE_TYPE,
            'en',
            ['webspace' => 'example'],
        );

        $this->assertSame('tree_leaf_edit', $this->getRouteFieldMetadataModeParam($routeMetadata));
    }

    public function testRoutePropertyModeSetInMultipleTemplates(): void
    {
        $this->webspace->setResourceLocatorStrategy('tree_full_edit');

        $routeMetadataDefault = $this->createRouteFieldMetadata();
        $routeMetadataOverview = $this->createRouteFieldMetadata();
        $defaultFormMetadata = $this->createFormMetadata('default', [$routeMetadataDefault]);
        $overviewFormMetadata = $this->createFormMetadata('overview', [$routeMetadataOverview]);
        $typedFormMetadata = $this->createTypedFormMetadata([$defaultFormMetadata, $overviewFormMetadata]);

        $this->webspaceRouteModeTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            PageInterface::TEMPLATE_TYPE,
            'en',
            ['webspace' => 'example'],
        );

        $this->assertSame('tree_full_edit', $this->getRouteFieldMetadataModeParam($routeMetadataDefault));
        $this->assertSame('tree_full_edit', $this->getRouteFieldMetadataModeParam($routeMetadataOverview));
    }

    public function testRoutePropertyModeAlreadySet(): void
    {
        $this->webspace->setResourceLocatorStrategy('tree_leaf_edit');

        $routeMetadata = $this->createRouteFieldMetadata();
        $optionMetadata = new OptionMetadata();
        $optionMetadata->setName('mode');
        $optionMetadata->setValue('tree_full_edit');
        $routeMetadata->addOption($optionMetadata);

        $formMetadata = $this->createFormMetadata('default', [$routeMetadata]);
        $typedFormMetadata = $this->createTypedFormMetadata([$formMetadata]);

        $this->webspaceRouteModeTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            PageInterface::TEMPLATE_TYPE,
            'en',
            ['webspace' => 'example'],
        );

        $this->assertSame('tree_full_edit', $this->getRouteFieldMetadataModeParam($routeMetadata));
    }

    public function testRoutePropertyNotSetForNoWebspace(): void
    {
        $this->webspace->setResourceLocatorStrategy('tree_leaf_edit');

        $routeMetadata = $this->createRouteFieldMetadata();
        $formMetadata = $this->createFormMetadata('default', [$routeMetadata]);
        $typedFormMetadata = $this->createTypedFormMetadata([$formMetadata]);

        $this->webspaceRouteModeTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            PageInterface::TEMPLATE_TYPE,
            'en',
            [],
        );

        $this->assertNull($this->getRouteFieldMetadataModeParam($routeMetadata));
    }

    public function testRoutePropertyNotSetForNonePages(): void
    {
        $this->webspace->setResourceLocatorStrategy('tree_leaf_edit');

        $routeMetadata = $this->createRouteFieldMetadata();
        $formMetadata = $this->createFormMetadata('default', [$routeMetadata]);
        $typedFormMetadata = $this->createTypedFormMetadata([$formMetadata]);

        $this->webspaceRouteModeTypedFormMetadataVisitor->visitTypedFormMetadata(
            $typedFormMetadata,
            'example',
            'en',
            ['webspace' => 'example'],
        );

        $this->assertNull($this->getRouteFieldMetadataModeParam($routeMetadata));
    }

    /**
     * @param array<FormMetadata> $formMetadataList
     */
    private function createTypedFormMetadata(array $formMetadataList): TypedFormMetadata
    {
        $typedFormMetadata = new TypedFormMetadata();
        foreach ($formMetadataList as $formMetadata) {
            $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);
        }

        return $typedFormMetadata;
    }

    /**
     * @param array<ItemMetadata> $itemMetadataList
     */
    private function createFormMetadata(string $key, array $itemMetadataList): FormMetadata
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey($key);
        foreach ($itemMetadataList as $itemMetadata) {
            $formMetadata->addItem($itemMetadata);
        }

        return $formMetadata;
    }

    /**
     * @param array<ItemMetadata> $itemMetadataList
     */
    private function createSectionMetadata(array $itemMetadataList): SectionMetadata
    {
        $sectionMetadata = new SectionMetadata('url');
        foreach ($itemMetadataList as $itemMetadata) {
            $sectionMetadata->addItem($itemMetadata);
        }

        return $sectionMetadata;
    }

    private function createRouteFieldMetadata(): FieldMetadata
    {
        return $this->createFieldMetadata('url', 'route');
    }

    private function createFieldMetadata(string $name, string $type): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata($name);
        $fieldMetadata->setType($type);

        return $fieldMetadata;
    }

    private function getRouteFieldMetadataModeParam(FieldMetadata $routeMetadata): ?string
    {
        foreach ($routeMetadata->getOptions() as $option) {
            if ('mode' === $option->getName()) {
                $value = $option->getValue();
                $this->assertIsString($value);

                return $value;
            }
        }

        return null;
    }
}
