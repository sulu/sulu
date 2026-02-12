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

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Admin\MetadataVisitor;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManager;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Infrastructure\Sulu\Admin\MetadataVisitor\BlockSettingsFormMetadataVisitor;

class BlockSettingsFormMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    private BlockSettingsFormMetadataVisitor $blockSettingsFormMetadataVisitor;

    /**
     * @var ObjectProphecy<XmlFormMetadataLoader>
     */
    private $xmlFormMetadataLoader;

    /**
     * @var ObjectProphecy<WebspaceManager>
     */
    private $webspaceManager;

    protected function setUp(): void
    {
        $this->xmlFormMetadataLoader = $this->prophesize(XmlFormMetadataLoader::class);
        $this->webspaceManager = $this->prophesize(WebspaceManager::class);
        $this->blockSettingsFormMetadataVisitor = new BlockSettingsFormMetadataVisitor($this->xmlFormMetadataLoader->reveal(), $this->webspaceManager->reveal());
    }

    public function testVisitFormMetadata(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('content_block_settings');
        $segmentsFormMetadata = new FormMetadata();
        $segmentsMetadata = new SectionMetadata('segments');
        $webspace = new Webspace();
        $this->webspaceManager->getWebspaceCollection()->willReturn(
            new WebspaceCollection(['test' => $webspace]),
        );
        $segmentsFormMetadata->addItem($segmentsMetadata);

        $this->xmlFormMetadataLoader->getMetadata('content_block_settings_segments', 'en', [])->willReturn(
            $segmentsFormMetadata,
        );

        $this->blockSettingsFormMetadataVisitor->visitFormMetadata($formMetadata, 'en', []);

        $this->assertSame(
            [
                'segments' => [
                    'name' => 'segments',
                    'type' => 'section',
                ],
            ],
            \array_map(function(ItemMetadata $metadata): array {
                return [
                    'name' => $metadata->getName(),
                    'type' => $metadata->getType(),
                ];
            }, $formMetadata->getItems()),
        );
    }

    public function testVisitFormMetadataSegments(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('content_block_settings');
        $segmentsFormMetadata = new FormMetadata();
        $segmentsMetadata = new SectionMetadata('segments');
        $segmentsMetadata->setVisibleCondition('__webspace ? __webspace.segments|length &gt; 0 : true');
        $webspace = new Webspace();
        $segment1 = new Segment();
        $webspace->setSegments([$segment1]);
        $this->webspaceManager->getWebspaceCollection()->willReturn(
            new WebspaceCollection(['test' => $webspace]),
        );
        $segmentsFormMetadata->addItem($segmentsMetadata);
        $this->xmlFormMetadataLoader->getMetadata('content_block_settings_segments', 'en', [])->willReturn(
            $segmentsFormMetadata,
        );

        $this->blockSettingsFormMetadataVisitor->visitFormMetadata($formMetadata, 'en', []);

        $segmentsVisibleCondition = '';

        foreach ($formMetadata->getItems() as $item) {
            if ('segments' === $item->getName()) {
                $segmentsVisibleCondition = $item->getVisibleCondition();
            }
        }

        $this->assertSame('__webspace ? __webspace.segments|length &gt; 0 : true', $segmentsVisibleCondition);
    }

    public function testVisitFormMetadataNoSegments(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('content_block_settings');
        $segmentsFormMetadata = new FormMetadata();
        $segmentsMetadata = new SectionMetadata('segments');
        $segmentsMetadata->setVisibleCondition('__webspace ? __webspace.segments|length &gt; 0 : true');
        $webspace = new Webspace();
        $this->webspaceManager->getWebspaceCollection()->willReturn(
            new WebspaceCollection(['test' => $webspace]),
        );
        $segmentsFormMetadata->addItem($segmentsMetadata);
        $this->xmlFormMetadataLoader->getMetadata('content_block_settings_segments', 'en', [])->willReturn(
            $segmentsFormMetadata,
        );

        $this->blockSettingsFormMetadataVisitor->visitFormMetadata($formMetadata, 'en', []);

        $segmentsVisibleCondition = '';

        foreach ($formMetadata->getItems() as $item) {
            if ('segments' === $item->getName()) {
                $segmentsVisibleCondition = $item->getVisibleCondition();
            }
        }

        $this->assertSame('__webspace ? __webspace.segments|length &gt; 0 : false', $segmentsVisibleCondition);
    }
}
