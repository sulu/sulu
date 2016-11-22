<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Mapper\Translation;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Util\SuluNodeHelper;

class SuluNodeHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var PropertyInterface
     */
    private $property1;

    /**
     * @var PropertyInterface
     */
    private $property2;

    /**
     * @var PropertyInterface
     */
    private $property3;

    /**
     * @var PropertyInterface
     */
    private $property4;

    /**
     * @var PropertyInterface
     */
    private $property5;

    /**
     * @var PropertyInterface
     */
    private $property6;

    /**
     * @var PropertyInterface
     */
    private $property7;

    /**
     * @var SuluNodeHelper
     */
    private $helper;

    public function setUp()
    {
        $this->session = $this->getMockBuilder('PHPCR\SessionInterface')->disableOriginalConstructor()->getMock();
        $this->node = $this->getMockBuilder('Jackalope\Node')->disableOriginalConstructor()->getMock();
        $this->property1 = $this->getMockBuilder('Jackalope\Property')->disableOriginalConstructor()->getMock();
        $this->property2 = $this->getMockBuilder('Jackalope\Property')->disableOriginalConstructor()->getMock();
        $this->property3 = $this->getMockBuilder('Jackalope\Property')->disableOriginalConstructor()->getMock();
        $this->property4 = $this->getMockBuilder('Jackalope\Property')->disableOriginalConstructor()->getMock();
        $this->property5 = $this->getMockBuilder('Jackalope\Property')->disableOriginalConstructor()->getMock();
        $this->property6 = $this->getMockBuilder('Jackalope\Property')->disableOriginalConstructor()->getMock();
        $this->property7 = $this->getMockBuilder('Jackalope\Property')->disableOriginalConstructor()->getMock();

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
            $this->{'property' . $propertyIndex}->expects($this->any())
                ->method('getName')
                ->will($this->returnValue($propertyName));
            $this->{'property' . $propertyIndex}->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue($propertyValue));
            ++$propertyIndex;
        }

        $this->node->expects($this->any())
            ->method('getProperties')
            ->will($this->returnValue([
                $this->property1,
                $this->property2,
                $this->property3,
                $this->property4,
                $this->property5,
                $this->property6,
                $this->property7,
            ]));

        $this->helper = new SuluNodeHelper(
            $this->session,
            'i18n',
            [
                'base' => 'cmf',
                'snippet' => 'snippets',
            ]
        );
    }

    public function testGetLanguagesForNode()
    {
        $languages = $this->helper->getLanguagesForNode($this->node);

        // languages are only counted if they are on the "template" property
        $this->assertEquals(['fr', 'de'], $languages);
    }

    public function testLocalizedPropertyValues()
    {
        $localizedValues = $this->helper->getLocalizedPropertyValues($this->node, 'changer');

        // languages are only counted if they are on the "template" property
        $this->assertEquals([
            'fr' => 'One title',
            'de' => 'Four title',
        ], $localizedValues);
    }

    public function provideExtractWebspaceFromPath()
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

    /**
     * @dataProvider provideExtractWebspaceFromPath
     */
    public function testExtractWebspaceFromPath($path, $expected)
    {
        $res = $this->helper->extractWebspaceFromPath($path);
        $this->assertEquals($expected, $res);
    }

    public function provideExtractSnippetTypeFromPath()
    {
        return [
            ['/cmf/snippets/foobar/snippet1', 'foobar'],
            ['/cmf/snippets/bar-foo/snippet2', 'bar-foo'],
            ['/cmf/snippets', null, false],
            ['/cmf/snippets/bar', null, false],
            ['/cmf/snippets/animal/elephpant', 'animal'],
            ['', null, false],
        ];
    }

    /**
     * @dataProvider provideExtractSnippetTypeFromPath
     */
    public function testExtractSnippetTypeFromPath($path, $expected, $valid = true)
    {
        if (false === $valid) {
            $this->setExpectedException('\InvalidArgumentException');
        }

        $res = $this->helper->extractSnippetTypeFromPath($path);
        $this->assertEquals($expected, $res);
    }

    public function provideGetStructureTypeForNode()
    {
        return [
            ['sulu:snippet', 'snippet'],
            ['sulu:page', 'page'],
            ['sulu:foobar', null],
            ['', null],
        ];
    }

    /**
     * @dataProvider provideGetStructureTypeForNode
     */
    public function testGetStructureTypeForNode($nodeType, $expected)
    {
        $this->node->expects($this->any())
            ->method('getPropertyValueWithDefault')
            ->with('jcr:mixinTypes', [])
            ->will($this->returnValue([$nodeType]));

        $this->assertEquals($expected, $this->helper->getStructureTypeForNode($this->node));
    }

    public function provideHasSuluNodeType()
    {
        return [
            ['sulu:snippet', true],
            [['sulu:foobar', 'sulu:snippet'], true],
            ['sulu:page', false],
            ['sulu:foobar', false],
            ['', false],
        ];
    }

    /**
     * @dataProvider provideHasSuluNodeType
     */
    public function testHasSuluNodeType($nodeTypes, $expected)
    {
        $this->node->expects($this->any())
            ->method('getPropertyValueWithDefault')
            ->with('jcr:mixinTypes', [])
            ->will($this->returnValue(['sulu:snippet']));

        $this->assertEquals($expected, $this->helper->hasSuluNodeType($this->node, $nodeTypes));
    }

    public function testSiblingNodes()
    {
        for ($i = 1; $i <= 3; ++$i) {
            ${'node' . $i} = $this->getMockBuilder('Jackalope\Node')->disableOriginalConstructor()->getMock();
            ${'node' . $i}->expects($this->any())
                ->method('getPath')
                ->will($this->returnValue('/foobar/foobar-' . $i));
        }

        $node2->expects($this->any())
            ->method('getParent')
            ->will($this->returnValue($this->node));
        $this->node->expects($this->any())
            ->method('getNodes')
            ->will($this->returnValue([
                $node1, $node2, $node3,
            ]));

        $res = $this->helper->getNextNode($node2);
        $this->assertSame($node3->getPath(), $res->getPath());

        $res = $this->helper->getPreviousNode($node2);
        $this->assertSame($node1->getPath(), $res->getPath());
    }
}
