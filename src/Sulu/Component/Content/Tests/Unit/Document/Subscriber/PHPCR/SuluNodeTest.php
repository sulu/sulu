<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber\PHPCR;

use PHPCR\ItemVisitorInterface;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\PropertyType;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Document\Subscriber\PHPCR\SuluNode;

/**
 * Tests for calss SuluNode.
 */
class SuluNodeTest extends \PHPUnit_Framework_TestCase
{
    public function provideDelegateData()
    {
        return [
            ['getPath', '/test'],
            ['getName', 'test'],
            ['getAncestor', [$this->prophesize(NodeInterface::class)->reveal()], [1]],
            ['getParent', $this->prophesize(NodeInterface::class)->reveal()],
            ['getDepth', 1],
            ['getSession', $this->prophesize(SessionInterface::class)->reveal()],
            ['isNode', true],
            ['isNew', true],
            ['isModified', true],
            ['isSame', false, [$this->prophesize(NodeInterface::class)->reveal()]],
            ['accept', true, [$this->prophesize(ItemVisitorInterface::class)->reveal()]],
            ['revert', true],
            ['remove', null],
            ['addNode', $this->prophesize(NodeInterface::class)->reveal(), ['test-1', 'sulu:content']],
            ['addNodeAutoNamed', $this->prophesize(NodeInterface::class)->reveal(), ['test-1', 'sulu:content']],
            ['orderBefore', $this->prophesize(NodeInterface::class)->reveal(), ['test-1', 'test-2']],
            ['rename', null, ['test-1']],
            ['getNode', $this->prophesize(NodeInterface::class)->reveal(), ['test-1']],
            ['getNodes', [$this->prophesize(NodeInterface::class)->reveal()], ['test-*', 'sulu:*']],
            ['getNodeNames', ['test-1'], ['test-*', 'sulu:*']],
            ['getProperty', $this->prophesize(PropertyInterface::class)->reveal(), ['test-1']],
            ['getPropertyValue', 'test-1', ['test-1', PropertyType::TYPENAME_DATE]],
            ['getPropertyValueWithDefault', 'test-1', ['test-1', null]],
            ['getProperties', [$this->prophesize(PropertyInterface::class)->reveal()], ['test-*']],
            ['getPropertiesValues', ['test-1'], ['test-*', false]],
            ['getPrimaryItem', 'sulu:content'],
            ['getIdentifier', '123-123-123'],
            ['getIndex', 1],
            ['getReferences', [$this->prophesize(NodeInterface::class)->reveal()], ['sulu:content']],
            ['getWeakReferences', [$this->prophesize(NodeInterface::class)->reveal()], ['sulu:content']],
            ['hasNode', true, ['test-1']],
            ['hasProperty', true, ['test-1']],
            ['hasNodes', true],
            ['hasProperties', true],
            ['getPrimaryNodeType', 'sulu:content'],
            ['getMixinNodeTypes', ['sulu:content']],
            ['isNodeType', true, ['sulu:content']],
            ['setPrimaryType', null, ['sulu:content']],
            ['addMixin', null, ['sulu:content']],
            ['removeMixin', null, ['sulu:content']],
            ['setMixins', null, [['sulu:content']]],
            ['update', null, ['default']],
            ['getCorrespondingNodePath', '/test-1', ['default']],
            ['getSharedSet', [$this->prophesize(NodeInterface::class)->reveal()]],
            ['removeSharedSet', null],
            ['removeShare', null],
            ['isCheckedOut', true],
            ['isLocked', true],
            ['followLifecycleTransition', true, ['test-1']],
            ['getAllowedLifecycleTransitions', ['test-1']],
        ];
    }

    /**
     * @dataProvider provideDelegateData
     */
    public function testDelegate($functionName, $returnValue, $arguments = [])
    {
        $node = $this->prophesize(NodeInterface::class);

        call_user_func_array([$node, $functionName], $arguments)->willReturn($returnValue);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($returnValue, call_user_func_array([$suluNode, $functionName], $arguments));
    }

    public function testGetIterator()
    {
        $node = $this->prophesize(NodeInterface::class)->willImplement(\IteratorAggregate::class);
        $node->getIterator()->willReturn($this->prophesize(\Iterator::class)->reveal());

        $suluNode = new SuluNode($node->reveal());

        $this->assertInstanceOf(\Iterator::class, $suluNode->getIterator());
    }

    public function testSetProperty()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $node = $this->prophesize(NodeInterface::class)->willImplement(\IteratorAggregate::class);

        $node->setProperty('test-1', 'test', PropertyType::STRING)->willReturn($property->reveal());
        $node->getPropertyValueWithDefault('test-1', null)->willReturn(null);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($property->reveal(), $suluNode->setProperty('test-1', 'test', PropertyType::STRING));
    }

    public function testSetPropertySameType()
    {
        $property = $this->prophesize(PropertyInterface::class);
        $node = $this->prophesize(NodeInterface::class)->willImplement(\IteratorAggregate::class);

        $node->setProperty('test-1', 'test', PropertyType::STRING)->willReturn($property->reveal());
        $node->getPropertyValueWithDefault('test-1', null)->willReturn('same-type');

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($property->reveal(), $suluNode->setProperty('test-1', 'test', PropertyType::STRING));
    }

    public function testSetPropertyDifferentType()
    {
        $oldProperty = $this->prophesize(PropertyInterface::class);
        $property = $this->prophesize(PropertyInterface::class);
        $node = $this->prophesize(NodeInterface::class)->willImplement(\IteratorAggregate::class);

        $node->setProperty('test-1', 'test', PropertyType::STRING)->willReturn($property->reveal());
        $node->getPropertyValueWithDefault('test-1', null)->willReturn(['different-type']);
        $node->getProperty('test-1')->willReturn($oldProperty->reveal());
        $oldProperty->remove()->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($property->reveal(), $suluNode->setProperty('test-1', 'test', PropertyType::STRING));
    }
}
