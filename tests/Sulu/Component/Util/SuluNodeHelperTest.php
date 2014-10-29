<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper\Translation;

use Sulu\Component\Util\SuluNodeHelper;

class SuluNodeHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $properties;

    public function setUp()
    {
        $this->node = $this->getMockBuilder('Jackalope\Node')->disableOriginalConstructor()->getMock();
        $this->property1 = $this->getMockBuilder('Jackalope\Property')->disableOriginalConstructor()->getMock();
        $this->property2 = $this->getMockBuilder('Jackalope\Property')->disableOriginalConstructor()->getMock();
        $this->property3 = $this->getMockBuilder('Jackalope\Property')->disableOriginalConstructor()->getMock();
        $this->property4 = $this->getMockBuilder('Jackalope\Property')->disableOriginalConstructor()->getMock();
        $this->property5 = $this->getMockBuilder('Jackalope\Property')->disableOriginalConstructor()->getMock();

        $this->property1->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('i18n:fr-title'));
        $this->property2->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('bas:barfoo'));
        $this->property3->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('i18n:it-barfoo'));
        $this->property4->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('i18n:de-title'));
        $this->property5->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('i18n:de-bbbaaaa'));

        $this->node->expects($this->any())
            ->method('getProperties')
            ->will($this->returnValue(array(
                $this->property1,
                $this->property2,
                $this->property3,
                $this->property4,
            )));

        $this->helper = new SuluNodeHelper(
            'i18n',
            array(
                'base' => 'cmf',
                'snippet' => 'snippets'
            )
        );
    }

    public function testGetLanguagesForNode()
    {
        $languages = $this->helper->getLanguagesForNode($this->node);

        // languages are only counted if they are on the "template" property
        $this->assertEquals(array('fr', 'de'), $languages);
    }

    public function provideExtractWebspaceFromPath()
    {
        return array(
            array('/cmf/sulu_io/content/articles/article-one', 'sulu_io'),
            array('/cmfcontent/articles/article-one', null),
            array('/cmf/webspace_five', null),
            array('/cmf/webspace_five/foo/bar/dar/ding', 'webspace_five'),
            array('', null),
            array('asdasd', null),
        );
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
        return array(
            array('/cmf/snippets/foobar/snippet1', 'foobar'),
            array('/cmf/snippets/bar-foo/snippet2', 'bar-foo'),
            array('/cmf/snippets', null, false),
            array('/cmf/snippets/bar', null, false),
            array('/cmf/snippets/animal/elephpant', 'animal'),
            array('', null, false),
        );
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
        return array(
            array('sulu:snippet', 'snippet'),
            array('sulu:page', 'page'),
            array('sulu:foobar', null),
            array('', null),
        );
    }

    /**
     * @dataProvider provideGetStructureTypeForNode
     */
    public function testGetStructureTypeForNode($nodeType, $expected)
    {
        $this->node->expects($this->any())
            ->method('getPropertyValueWithDefault')
            ->with('jcr:mixinTypes', array())
            ->will($this->returnValue(array($nodeType)));

        $this->assertEquals($expected, $this->helper->getStructureTypeForNode($this->node));
    }
}
