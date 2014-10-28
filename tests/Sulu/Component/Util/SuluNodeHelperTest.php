<?php

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
            ->will($this->returnValue('i18n:fr-template'));
        $this->property2->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('bas:barfoo'));
        $this->property3->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('i18n:it-barfoo'));
        $this->property4->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('i18n:de-template'));
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

        $this->helper = new SuluNodeHelper('i18n');
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
            array('/cmfcontent/articles/article-one', 'articles'),
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
}
