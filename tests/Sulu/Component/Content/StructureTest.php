<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use DateTime;

class StructureTest extends \PHPUnit_Framework_TestCase
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

        $this->structure = $this->getMockBuilder('Sulu\Component\Content\Structure')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->structure->setType(StructureType::getGhost('en_us'));
        $this->structure->setChanged(new DateTime('2014-03-18'));
        $this->structure->setCreated(new DateTime('2014-03-17'));
        $this->structure->setGlobalState(1);
        $this->structure->setNodeState(2);
        $this->structure->setPublished(new DateTime('2014-03-16'));
        $this->structure->setNavigation(true);
        $this->structure->setHasTranslation(true);

    }

    public function testToArray()
    {
        $this->titleProperty->expects($this->any())->method('getTags')->will(
            $this->returnValue(array())
        );
        $this->titleProperty->expects($this->any())->method('getName')->will(
            $this->returnValue('title')
        );
        $this->titleProperty->expects($this->any())->method('getValue')->will(
            $this->returnValue('titlevalue')
        );

        $this->testProperty->expects($this->any())->method('getTags')->will(
            $this->returnValue(array())
        );
        $this->testProperty->expects($this->any())->method('getName')->will(
            $this->returnValue('test')
        );
        $this->testProperty->expects($this->any())->method('getValue')->will(
            $this->returnValue('testvalue')
        );

        $addMethod = new \ReflectionMethod('Sulu\Component\Content\Structure', 'add');
        $addMethod->setAccessible(true);

        $addMethod->invoke($this->structure, $this->titleProperty);
        $addMethod->invoke($this->structure, $this->testProperty);

        $structureArray = $this->structure->toArray();

        $this->assertEquals(2, $structureArray['nodeState']);
        $this->assertEquals(1, $structureArray['globalState']);
        $this->assertEquals(true, $structureArray['publishedState']);
        $this->assertEquals(true, $structureArray['navigation']);
        $this->assertEquals(false, $structureArray['hasSub']);
        $this->assertEquals(new DateTime('2014-03-17'), $structureArray['created']);
        $this->assertEquals(new DateTime('2014-03-18'), $structureArray['changed']);
        $this->assertEquals('ghost', $structureArray['type']['name']);
        $this->assertEquals('en_us', $structureArray['type']['value']);
        $this->assertEquals('titlevalue', $structureArray['title']);
        $this->assertEquals('testvalue', $structureArray['test']);

        $structureArray = $this->structure->toArray(false);

        $this->assertEquals(2, $structureArray['nodeState']);
        $this->assertEquals(1, $structureArray['globalState']);
        $this->assertEquals(true, $structureArray['navigation']);
        $this->assertEquals(false, $structureArray['hasSub']);
        $this->assertEquals('ghost', $structureArray['type']['name']);
        $this->assertEquals('en_us', $structureArray['type']['value']);
        $this->assertEquals('titlevalue', $structureArray['title']);
        $this->assertArrayNotHasKey('published', $structureArray);
        $this->assertArrayNotHasKey('test', $structureArray);
        $this->assertArrayNotHasKey('created', $structureArray);
        $this->assertArrayNotHasKey('changed', $structureArray);
    }

    public function testAddProperty()
    {
        $this->titleProperty->expects($this->any())->method('getTags')->will(
            $this->returnValue(
                array(
                    new PropertyTag('tag1', 1),
                    new PropertyTag('tag2', 6),
                    new PropertyTag('tag3', 10),
                    new PropertyTag('tag4', 10),
                )
            )
        );
        $this->titleProperty->expects($this->any())->method('getName')->will(
            $this->returnValue('title')
        );
        $this->titleProperty->expects($this->any())->method('getValue')->will(
            $this->returnValue('titlevalue')
        );

        $this->testProperty->expects($this->any())->method('getTags')->will(
            $this->returnValue(
                array(
                    new PropertyTag('tag1', 10),
                    new PropertyTag('tag2', 5),
                    new PropertyTag('tag3', 1),
                    new PropertyTag('tag5', 1),
                )
            )
        );
        $this->testProperty->expects($this->any())->method('getName')->will(
            $this->returnValue('test')
        );
        $this->testProperty->expects($this->any())->method('getValue')->will(
            $this->returnValue('testvalue')
        );

        $addMethod = new \ReflectionMethod('Sulu\Component\Content\Structure', 'add');
        $addMethod->setAccessible(true);

        $addMethod->invoke($this->structure, $this->titleProperty);
        $addMethod->invoke($this->structure, $this->testProperty);

        $tag1Property = $this->structure->getPropertyByTagName('tag1');
        $tag2Property = $this->structure->getPropertyByTagName('tag2');
        $tag3Property = $this->structure->getPropertyByTagName('tag3');
        $tag4Property = $this->structure->getPropertyByTagName('tag4');
        $tag5Property = $this->structure->getPropertyByTagName('tag5');

        $this->assertEquals($tag1Property, $this->testProperty);
        $this->assertEquals($tag2Property, $this->titleProperty);
        $this->assertEquals($tag3Property, $this->titleProperty);
        $this->assertEquals($tag4Property, $this->titleProperty);
        $this->assertEquals($tag5Property, $this->testProperty);

        $this->setExpectedException('\Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException');
        $this->structure->getPropertyByTagName('test-tag');
    }
}
