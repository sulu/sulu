<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Mapper\Translation;

use Jackalope\Node;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Util\SuluNodeHelper;

class SuluNodeHelperTest extends TestCase
{
    /**
     * @var MockObject&SessionInterface
     */
    private $session;

    /**
     * @var MockObject&StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var MockObject&NodeInterface
     */
    private $node;

    /**
     * @var MockObject&PropertyInterface
     */
    private $property1;

    /**
     * @var MockObject&PropertyInterface
     */
    private $property2;

    /**
     * @var MockObject&PropertyInterface
     */
    private $property3;

    /**
     * @var MockObject&PropertyInterface
     */
    private $property4;

    /**
     * @var MockObject&PropertyInterface
     */
    private $property5;

    /**
     * @var MockObject&PropertyInterface
     */
    private $property6;

    /**
     * @var MockObject&PropertyInterface
     */
    private $property7;

    /**
     * @var SuluNodeHelper
     */
    private $helper;

    public function setUp(): void
    {
        $this->session = $this->getMockBuilder(SessionInterface::class)->disableOriginalConstructor()->getMock();
        $this->node = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
        $this->structureMetadataFactory = $this->getMockBuilder(StructureMetadataFactoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->property1 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();
        $this->property2 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();
        $this->property3 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();
        $this->property4 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();
        $this->property5 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();
        $this->property6 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();
        $this->property7 = $this->getMockBuilder(PropertyInterface::class)->disableOriginalConstructor()->getMock();

        $properties = [
            1 => $this->property1,
            2 => $this->property2,
            3 => $this->property3,
            4 => $this->property4,
            5 => $this->property5,
            6 => $this->property6,
            7 => $this->property7,
        ];

        $propertyIndex = 1;
        foreach ([
            'i18n:fr-changer' => 'One title',
            'bas:barfoo' => 'Two title',
            'i18n:it-barfoo' => 'Three title',
            'i18n:de-changer' => 'Four title',
            'i18n:de-bbbaaaa' => 'Five title',
            'i18n:de-seo-changer' => 'Six title',
            'i18n:de-de-changer' => 'Seven title',
        ] as $propertyName => $propertyValue) {
            $properties[$propertyIndex]->expects($this->any())
                ->method('getName')
                ->willReturn($propertyName);
            $properties[$propertyIndex]->expects($this->any())
                ->method('getValue')
                ->willReturn($propertyValue);
            ++$propertyIndex;
        }

        $this->node->expects($this->any())
            ->method('getProperties')
            ->willReturn(new \ArrayIterator($properties));

        $this->helper = new SuluNodeHelper(
            $this->session,
            'i18n',
            [
                'base' => 'cmf',
                'snippet' => 'snippets',
            ],
            $this->structureMetadataFactory
        );
    }

    public function testGetLanguagesForNode(): void
    {
        $languages = $this->helper->getLanguagesForNode($this->node);

        // languages are only counted if they are on the "template" property
        $this->assertEquals(['fr', 'de'], $languages);
    }

    public function testLocalizedPropertyValues(): void
    {
        $localizedValues = $this->helper->getLocalizedPropertyValues($this->node, 'changer');

        // languages are only counted if they are on the "template" property
        $this->assertEquals([
            'fr' => 'One title',
            'de' => 'Four title',
        ], $localizedValues);
    }

    public static function provideExtractWebspaceFromPath()
    {
        return [
            ['/cmf/sulu_io/content/articles/article-one', 'sulu_io'],
            ['/cmfcontent/articles/article-one', null],
            ['/cmf/webspace_five', null],
            ['/cmf/webspace_five/foo/bar/dar/ding', 'webspace_five'],
            ['', null],
            ['asdasd', null],
            ['/cmf/sulu-io/content/articles', 'sulu-io'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideExtractWebspaceFromPath')]
    public function testExtractWebspaceFromPath($path, $expected): void
    {
        $res = $this->helper->extractWebspaceFromPath($path);
        $this->assertEquals($expected, $res);
    }

    public static function provideGetStructureTypeForNode()
    {
        return [
            ['sulu:page', 'page'],
            ['sulu:foobar', null],
            ['', null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideGetStructureTypeForNode')]
    public function testGetStructureTypeForNode($nodeType, $expected): void
    {
        $this->node->expects($this->any())
            ->method('getPropertyValueWithDefault')
            ->with('jcr:mixinTypes', [])
            ->willReturn([$nodeType]);

        $this->assertEquals($expected, $this->helper->getStructureTypeForNode($this->node));
    }

    public static function provideHasSuluNodeType()
    {
        return [
            [['sulu:foobar', 'sulu:page'], true],
            ['sulu:page', true],
            ['sulu:foobar', false],
            ['', false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideHasSuluNodeType')]
    public function testHasSuluNodeType($nodeTypes, $expected): void
    {
        $this->node->expects($this->any())
            ->method('getPropertyValueWithDefault')
            ->with('jcr:mixinTypes', [])
            ->willReturn(['sulu:page']);

        $this->assertEquals($expected, $this->helper->hasSuluNodeType($this->node, $nodeTypes));
    }

    public function testSiblingNodes(): void
    {
        /** @var array<MockObject&Node> $nodes */
        $nodes = [];
        for ($i = 1; $i <= 3; ++$i) {
            $nodes[$i] = $this->getMockBuilder(Node::class)->disableOriginalConstructor()->getMock();
            $nodes[$i]->expects($this->any())
                ->method('getPath')
                ->willReturn('/foobar/foobar-' . $i);
        }

        $node1 = $nodes[1];
        $node2 = $nodes[2];
        $node3 = $nodes[3];

        $iterator = new \ArrayIterator([
            $node1, $node2, $node3,
        ]);
        $node2->expects($this->any())
            ->method('getParent')
            ->willReturn($this->node);
        $this->node->expects($this->any())
            ->method('getNodes')
            ->willReturn($iterator);

        $res = $this->helper->getNextNode($node2);
        $this->assertNotNull($res);
        $this->assertSame($node3->getPath(), $res->getPath());

        $iterator->rewind();
        $res = $this->helper->getPreviousNode($node2);
        $this->assertNotNull($res);
        $this->assertSame($node1->getPath(), $res->getPath());
    }
}
