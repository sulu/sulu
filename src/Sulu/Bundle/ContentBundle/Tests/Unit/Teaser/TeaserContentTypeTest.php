<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Teaser;

use Sulu\Bundle\ContentBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderPoolInterface;
use Sulu\Bundle\ContentBundle\Teaser\Teaser;
use Sulu\Bundle\ContentBundle\Teaser\TeaserContentType;
use Sulu\Bundle\ContentBundle\Teaser\TeaserManagerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\StructureInterface;

class TeaserContentTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $template = 'SuluTestBundle:Templates:content-type.html.twig';

    /**
     * @var TeaserProviderPoolInterface
     */
    private $teaserProviderPool;

    /**
     * @var TeaserManagerInterface
     */
    private $teaserManager;

    /**
     * @var TeaserContentType
     */
    private $contentType;

    protected function setUp()
    {
        $this->teaserProviderPool = $this->prophesize(TeaserProviderPoolInterface::class);
        $this->teaserManager = $this->prophesize(TeaserManagerInterface::class);

        $this->contentType = new TeaserContentType(
            $this->template,
            $this->teaserProviderPool->reveal(),
            $this->teaserManager->reveal()
        );
    }

    public function testGetTemplate()
    {
        $this->assertEquals($this->template, $this->contentType->getTemplate());
    }

    public function testGetDefaultParameter()
    {
        $configuration = [new TeaserConfiguration('sulu_test.content', 'content@sulucontent')];
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
        $items = [['type' => 'content', 'id' => '123-123-123'], ['type' => 'media', 'id' => 1]];

        $teasers = array_map(
            function ($item) {
                $teaser = $this->prophesize(Teaser::class);
                $teaser->getType()->willReturn($item['type']);
                $teaser->getId()->willReturn($item['id']);

                return $teaser->reveal();
            },
            $items
        );

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['items' => $items]);
        $property->getStructure()->willReturn($structure);

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
}
