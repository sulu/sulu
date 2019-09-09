<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Teaser;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PageBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderPoolInterface;
use Sulu\Bundle\PageBundle\Teaser\Teaser;
use Sulu\Bundle\PageBundle\Teaser\TeaserContentType;
use Sulu\Bundle\PageBundle\Teaser\TeaserManagerInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreNotExistsException;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\StructureInterface;

class TeaserContentTypeTest extends TestCase
{
    /**
     * @var TeaserProviderPoolInterface
     */
    private $teaserProviderPool;

    /**
     * @var TeaserManagerInterface
     */
    private $teaserManager;

    /**
     * @var ReferenceStorePoolInterface
     */
    private $referenceStorePool;

    /**
     * @var ReferenceStoreInterface
     */
    private $mediaReferenceStore;

    /**
     * @var TeaserContentType
     */
    private $contentType;

    protected function setUp(): void
    {
        $this->teaserProviderPool = $this->prophesize(TeaserProviderPoolInterface::class);
        $this->teaserManager = $this->prophesize(TeaserManagerInterface::class);
        $this->referenceStorePool = $this->prophesize(ReferenceStorePoolInterface::class);
        $this->mediaReferenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->referenceStorePool->getStore('media')->willReturn($this->mediaReferenceStore->reveal());

        $this->contentType = new TeaserContentType(
            $this->teaserProviderPool->reveal(),
            $this->teaserManager->reveal(),
            $this->referenceStorePool->reveal()
        );
    }

    public function testGetDefaultParameter()
    {
        $configuration = [new TeaserConfiguration('content', 'pages', 'column_list', ['title'], 'Choose')];
        $this->teaserProviderPool->getConfiguration()->willReturn($configuration);

        $this->assertEquals(
            [
                'providerConfiguration' => $configuration,
                'present_as' => new PropertyParameter('present_as', [], 'collection'),
            ],
            $this->contentType->getDefaultParams()
        );
    }

    public function testGetContentDataEmpty()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn([]);

        $this->assertEquals([], $this->contentType->getContentData($property->reveal()));
    }

    public function testGetContentData()
    {
        $items = [
            ['type' => 'content', 'id' => '123-123-123', 'mediaId' => 15],
            ['type' => 'media', 'id' => 1, 'mediaId' => null],
        ];

        $teasers = array_map(
            function($item) {
                $teaser = $this->prophesize(Teaser::class);
                $teaser->getType()->willReturn($item['type']);
                $teaser->getId()->willReturn($item['id']);
                $teaser->getMediaId()->willReturn($item['mediaId']);

                return $teaser->reveal();
            },
            $items
        );

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['items' => $items]);
        $property->getStructure()->willReturn($structure);

        $this->mediaReferenceStore->add(15);

        $this->teaserManager->find($items, 'de')->shouldBeCalled()->willReturn($teasers);

        $this->assertEquals($teasers, $this->contentType->getContentData($property->reveal()));
    }

    public function testGetViewDataEmpty()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['presentAs' => 'col1']);

        $this->assertEquals(
            ['items' => [], 'presentAs' => 'col1'],
            $this->contentType->getViewData($property->reveal())
        );
    }

    public function testGetViewData()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn([]);

        $this->assertEquals(
            ['items' => [], 'presentAs' => null],
            $this->contentType->getViewData($property->reveal())
        );
    }

    public function testPreResolve()
    {
        $data = [
            'items' => [
                ['type' => 'article', 'id' => 1],
                ['type' => 'test', 'id' => 2],
                ['type' => 'content', 'id' => 3],
            ],
        ];

        $articleStore = $this->prophesize(ReferenceStoreInterface::class);
        $contentStore = $this->prophesize(ReferenceStoreInterface::class);

        $this->referenceStorePool->getStore('article')->willReturn($articleStore->reveal());
        $this->referenceStorePool->getStore('content')->willReturn($contentStore->reveal());
        $this->referenceStorePool->getStore('test')
            ->willThrow(
                new ReferenceStoreNotExistsException('test', ['article', 'content'])
            );

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn($data);

        $this->contentType->preResolve($property->reveal());

        $articleStore->add(1)->shouldBeCalled();
        $contentStore->add(3)->shouldBeCalled();
    }
}
