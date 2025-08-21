<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Content\Types;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Webspace;

class MediaSelectionContentTypeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var MediaSelectionContentType
     */
    private $mediaSelection;

    /**
     * @var ObjectProphecy<ReferenceStoreInterface>
     */
    private $mediaReferenceStore;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var ObjectProphecy<MediaManagerInterface>
     */
    private $mediaManager;

    protected function setUp(): void
    {
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);
        $this->mediaReferenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->webspace = new Webspace();
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace);

        $this->mediaSelection = new MediaSelectionContentType(
            $this->mediaManager->reveal(),
            $this->mediaReferenceStore->reveal(),
            $this->requestAnalyzer->reveal(),
            ['view' => 64],
        );
    }

    public function testWrite(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property')->shouldBeCalled();
        $property->getValue()->willReturn(
            [
                'ids' => [1, 2, 3, 4],
                'displayOption' => 'right',
                'config' => ['conf1' => 1, 'conf2' => 2],
            ]
        )->shouldBeCalled();

        $node = $this->prophesize(NodeInterface::class);
        $node->setProperty(
            'property',
            \json_encode(
                [
                    'ids' => [1, 2, 3, 4],
                    'displayOption' => 'right',
                    'config' => ['conf1' => 1, 'conf2' => 2],
                ]
            )
        )->shouldBeCalled();

        $this->mediaSelection->write($node->reveal(), $property->reveal(), 0, 'test', 'en', 's');
    }

    public function testWriteWithPassedContainer(): void
    {
        $property = $this->prophesize(PropertyInterface::class);

        $property->getName()->willReturn('property')->shouldBeCalled();
        $property->getValue()->willReturn(
            [
                'ids' => [1, 2, 3, 4],
                'displayOption' => 'right',
                'config' => ['conf1' => 1, 'conf2' => 2],
                'data' => ['data1', 'data2'],
            ]
        )->shouldBeCalled();

        $node = $this->prophesize(NodeInterface::class);
        $node->setProperty(
            'property',
            \json_encode(
                [
                    'ids' => [1, 2, 3, 4],
                    'displayOption' => 'right',
                    'config' => ['conf1' => 1, 'conf2' => 2],
                ]
            )
        )->shouldBeCalled();

        $this->mediaSelection->write($node->reveal(), $property->reveal(), 0, 'test', 'en', 's');
    }

    public function testRead(): void
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('property', '{"ids": []}')->willReturn($config)->shouldBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property')->shouldBeCalled();
        $property->setValue(\json_decode($config, true))->willReturn(null)->shouldBeCalled();

        $this->mediaSelection->read($node->reveal(), $property->reveal(), 'test', 'en', 's');
    }

    public function testReadWithInvalidValue(): void
    {
        $config = '[]';

        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('property', '{"ids": []}')->willReturn($config)->shouldBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property')->shouldBeCalled();
        $property->setValue(null)->willReturn(null)->shouldBeCalled();

        $this->mediaSelection->read($node->reveal(), $property->reveal(), 'test', 'en', 's');
    }

    public function testReadWithType(): void
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('property', '{"ids": []}')->willReturn($config)->shouldBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property')->shouldBeCalled();
        $property->setValue(\json_decode($config, true))->willReturn(null)->shouldBeCalled();
        $property->getParams()->willReturn(['types' => 'document']);

        $this->mediaSelection->read($node->reveal(), $property->reveal(), 'test', 'en', 's');
    }

    public function testReadWithMultipleTypes(): void
    {
        $config = '{"config":{"conf1": 1, "conf2": 2}, "displayOption": "right", "ids": [1,2,3,4]}';

        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('property', '{"ids": []}')->willReturn($config);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('property')->shouldBeCalled();
        $property->setValue(\json_decode($config, true))->willReturn(null)->shouldBeCalled();

        $this->mediaSelection->read($node->reveal(), $property->reveal(), 'test', 'en', 's');
    }

    public function testGetContentData(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['ids' => [1, 2, 3]]);
        $property->getParams()->willReturn([]);

        $structure = $this->prophesize(StructureInterface::class);
        $property->getStructure()->willReturn($structure->reveal());

        $this->requestAnalyzer->getWebspace()->willReturn(null);

        $this->mediaManager->getByIds([1, 2, 3], null, null)->shouldBeCalled();

        $result = $this->mediaSelection->getContentData($property->reveal());
    }

    public function testGetContentDataWithPermissions(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['ids' => [1, 2, 3]]);
        $property->getParams()->willReturn([]);

        $structure = $this->prophesize(StructureInterface::class);
        $property->getStructure()->willReturn($structure->reveal());

        $security = new Security();
        $security->setSystem('website');
        $security->setPermissionCheck(true);
        $this->webspace->setSecurity($security);

        $this->mediaManager->getByIds([1, 2, 3], null, 64)->shouldBeCalled();

        $result = $this->mediaSelection->getContentData($property->reveal());
    }

    public function testPreResolve(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn(['ids' => [1, 2, 3]]);

        $this->mediaSelection->preResolve($property->reveal());

        $this->mediaReferenceStore->add(1)->shouldBeCalled();
        $this->mediaReferenceStore->add(2)->shouldBeCalled();
        $this->mediaReferenceStore->add(3)->shouldBeCalled();
    }

    public function testGetReferencesWithNullProperty(): void
    {
        $property = new Property(
            'media',
            new Metadata([]),
            'single_media_selection',
        );
        $property->setValue(null);

        $referenceCollector = $this->prophesize(ReferenceCollector::class);
        $referenceCollector->addReference(Argument::cetera())->shouldNotHaveBeenCalled();

        $this->mediaSelection->getReferences($property, $referenceCollector->reveal());
    }

    public function testGetReferences(): void
    {
        $property = new Property(
            'media',
            new Metadata([]),
            'media_selection',
        );
        $property->setValue(['ids' => [1, 2]]);

        $referenceCollector = $this->prophesize(ReferenceCollector::class);
        $referenceCollector->addReference(
            'media',
            1,
            'media'
        )->shouldBeCalled();
        $referenceCollector->addReference(
            'media',
            2,
            'media'
        )->shouldBeCalled();

        $this->mediaSelection->getReferences($property, $referenceCollector->reveal());
    }
}
