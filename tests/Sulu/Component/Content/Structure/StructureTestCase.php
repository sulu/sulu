<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure;

use DateTime;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\StructureTag;
use Symfony\Component\Config\Definition\Exception\Exception;

abstract class StructureTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var PropertyInterface
     */
    private $titleProperty;

    /**
     * @var PropertyInterface
     */
    private $testProperty;

    public function setUp()
    {
        $this->titleProperty = $this->getMockBuilder('Sulu\Component\Content\PropertyInterface')
            ->setMockClassName('TitleProperty')
            ->getMock();

        $this->testProperty = $this->getMockBuilder('Sulu\Component\Content\PropertyInterface')
            ->setMockClassName('TestProperty')
            ->getMock();

        $this->structure = $this->getStructure();
    }

    abstract protected function getStructure();

    public function testToArray()
    {
        $this->titleProperty->expects($this->any())->method('getTags')->will(
            $this->returnValue(array())
        );
        $this->titleProperty->expects($this->any())->method('getName')->will(
            $this->returnValue('title')
        );
        $this->titleProperty->expects($this->any())->method('toArray')->will(
            $this->returnValue('titlevalue')
        );

        $this->testProperty->expects($this->any())->method('getTags')->will(
            $this->returnValue(array())
        );
        $this->testProperty->expects($this->any())->method('getName')->will(
            $this->returnValue('test')
        );
        $this->testProperty->expects($this->any())->method('toArray')->will(
            $this->returnValue('testvalue')
        );

        $addMethod = new \ReflectionMethod('Sulu\Component\Content\Structure', 'addChild');
        $addMethod->setAccessible(true);

        $addMethod->invoke($this->structure, $this->titleProperty);
        $addMethod->invoke($this->structure, $this->testProperty);

        $structureArray = $this->structure->toArray();

        $this->assertEquals(2, $structureArray['nodeState']);
        $this->assertEquals(true, $structureArray['publishedState']);
        $this->assertEquals(true, $structureArray['navContexts']);
        $this->assertEquals(false, $structureArray['hasSub']);
        $this->assertEquals(new DateTime('2014-03-17'), $structureArray['created']);
        $this->assertEquals(new DateTime('2014-03-18'), $structureArray['changed']);
        $this->assertEquals('ghost', $structureArray['type']['name']);
        $this->assertEquals('en_us', $structureArray['type']['value']);
        $this->assertEquals('titlevalue', $structureArray['title']);
        $this->assertEquals('testvalue', $structureArray['test']);

        $structureArray = $this->structure->toArray(false);

        $this->assertEquals(2, $structureArray['nodeState']);
        $this->assertEquals(true, $structureArray['navContexts']);
        $this->assertEquals(false, $structureArray['hasSub']);
        $this->assertEquals('ghost', $structureArray['type']['name']);
        $this->assertEquals('en_us', $structureArray['type']['value']);
        $this->assertEquals('titlevalue', $structureArray['title']);
        $this->assertArrayNotHasKey('published', $structureArray);
        $this->assertArrayNotHasKey('test', $structureArray);
        $this->assertArrayNotHasKey('created', $structureArray);
        $this->assertArrayNotHasKey('changed', $structureArray);
    }

    /**
     * @var PropertyTag[]
     */
    private $titlePropertyTags;

    public function returnCallbackTitleTag()
    {
        $args = func_get_args();
        foreach ($this->titlePropertyTags as $tag) {
            if ($tag->getName() == $args[0]) {
                return $tag;
            }
        }
        throw new Exception('tag not found');
    }

    /**
     * @var PropertyTag[]
     */
    private $testPropertyTags;

    public function returnCallbackTestTag()
    {
        $args = func_get_args();
        foreach ($this->testPropertyTags as $tag) {
            if ($tag->getName() == $args[0]) {
                return $tag;
            }
        }
        throw new Exception('tag not found');
    }

    public function testAddProperty()
    {
        $this->titlePropertyTags = array(
            new PropertyTag('tag1', 1),
            new PropertyTag('tag2', 6),
            new PropertyTag('tag3', 10),
            new PropertyTag('tag4', 10),
        );

        $this->titleProperty->expects($this->any())->method('getTags')->will(
            $this->returnValue($this->titlePropertyTags)
        );
        $this->titleProperty->expects($this->any())->method('getName')->will(
            $this->returnValue('title')
        );
        $this->titleProperty->expects($this->any())->method('getValue')->will(
            $this->returnValue('titlevalue')
        );
        $this->titleProperty->expects($this->any())->method('getTag')->will(
            $this->returnCallback(array($this, 'returnCallbackTitleTag'))
        );

        $this->testPropertyTags = array(
            new PropertyTag('tag1', 10),
            new PropertyTag('tag2', 5),
            new PropertyTag('tag3', 1),
            new PropertyTag('tag5', 1),
        );
        $this->testProperty->expects($this->any())->method('getTags')->will(
            $this->returnValue($this->testPropertyTags)
        );
        $this->testProperty->expects($this->any())->method('getName')->will(
            $this->returnValue('test')
        );
        $this->testProperty->expects($this->any())->method('getValue')->will(
            $this->returnValue('testvalue')
        );
        $this->titleProperty->expects($this->any())->method('getTag')->will(
            $this->returnCallback(array($this, 'returnCallbackTestTag'))
        );

        $addMethod = new \ReflectionMethod('Sulu\Component\Content\Structure', 'addChild');
        $addMethod->setAccessible(true);

        $addMethod->invoke($this->structure, $this->titleProperty);
        $addMethod->invoke($this->structure, $this->testProperty);

        $tag1PropertyH = $this->structure->getPropertyByTagName('tag1', true);
        $tag2PropertyH = $this->structure->getPropertyByTagName('tag2', true);
        $tag3PropertyH = $this->structure->getPropertyByTagName('tag3', true);
        $tag4PropertyH = $this->structure->getPropertyByTagName('tag4', true);
        $tag5PropertyH = $this->structure->getPropertyByTagName('tag5', true);

        $tag1PropertyL = $this->structure->getPropertyByTagName('tag1', false);
        $tag2PropertyL = $this->structure->getPropertyByTagName('tag2', false);
        $tag3PropertyL = $this->structure->getPropertyByTagName('tag3', false);
        $tag4PropertyL = $this->structure->getPropertyByTagName('tag4', false);
        $tag5PropertyL = $this->structure->getPropertyByTagName('tag5', false);

        $tag1Properties = $this->structure->getPropertiesByTagName('tag1');
        $tag2Properties = $this->structure->getPropertiesByTagName('tag2');
        $tag3Properties = $this->structure->getPropertiesByTagName('tag3');
        $tag4Properties = $this->structure->getPropertiesByTagName('tag4');
        $tag5Properties = $this->structure->getPropertiesByTagName('tag5');

        /*
         * low to high
         * tag1: title, test
         * tag2: test, title
         * tag3: test, title
         * tag4: title
         * tag5: test
         */

        $this->assertEquals($tag1PropertyH, $this->testProperty);
        $this->assertEquals($tag2PropertyH, $this->titleProperty);
        $this->assertEquals($tag3PropertyH, $this->titleProperty);
        $this->assertEquals($tag4PropertyH, $this->titleProperty);
        $this->assertEquals($tag5PropertyH, $this->testProperty);

        $this->assertEquals($tag1PropertyL, $this->titleProperty);
        $this->assertEquals($tag2PropertyL, $this->testProperty);
        $this->assertEquals($tag3PropertyL, $this->testProperty);
        $this->assertEquals($tag4PropertyL, $this->titleProperty);
        $this->assertEquals($tag5PropertyL, $this->testProperty);

        $this->assertEquals(array(1 => $this->titleProperty, 10 => $this->testProperty), $tag1Properties);
        $this->assertEquals(array(5 => $this->testProperty, 6 => $this->titleProperty), $tag2Properties);
        $this->assertEquals(array(1 => $this->testProperty, 10 => $this->titleProperty), $tag3Properties);
        $this->assertEquals(array(10 => $this->titleProperty), $tag4Properties);
        $this->assertEquals(array(1 => $this->testProperty), $tag5Properties);

        $this->setExpectedException('Sulu\Component\Content\Exception\NoSuchPropertyException');
        $this->structure->getPropertyByTagName('test-tag', true);
    }

    public function testLanguageGettersAndSetters()
    {
        $data = array('foo', 'bar');
        $this->structure->setConcreteLanguages($data);
        $this->assertEquals($data, $this->structure->getConcreteLanguages());
        $this->structure->setEnabledShadowLanguages($data);
        $this->assertEquals($data, $this->structure->getEnabledShadowLanguages());
    }

    public function testStructureTag()
    {
        $structureTag = new StructureTag('foobar', array('one', 'two'));
        $this->structure->addStructureTag($structureTag);

        $this->assertTrue($this->structure->hasStructureTag('foobar'));
        $this->assertFalse($this->structure->hasStructureTag('barfoo'));
        $this->assertSame($structureTag, $this->structure->getStructureTag('foobar'));

        $structureTag = $this->structure->getStructureTag('foobar');
        $this->assertEquals('foobar', $structureTag->getName());
        $this->assertEquals(array('one', 'two'), $structureTag->getAttributes());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetUndefinedStructureTag()
    {
        $this->structure->getStructureTag('foobar');
    }
}
