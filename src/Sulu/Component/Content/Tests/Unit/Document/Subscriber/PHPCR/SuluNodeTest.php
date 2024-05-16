<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber\PHPCR;

use PHPCR\ItemVisitorInterface;
use PHPCR\NodeInterface;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\PropertyInterface;
use PHPCR\PropertyType;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Content\Document\Subscriber\PHPCR\SuluNode;

class SuluNodeTest extends TestCase
{
    use ProphecyTrait;

    public function testGetPath(): void
    {
        $path = '/test';
        $node = $this->prophesize(NodeInterface::class);
        $node->getPath()->willReturn($path);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($path, $suluNode->getPath());
    }

    public function testGetName(): void
    {
        $name = '/test';
        $node = $this->prophesize(NodeInterface::class);
        $node->getName()->willReturn($name);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($name, $suluNode->getName());
    }

    public function testGetAncestor(): void
    {
        $ancestor = $this->prophesize(NodeInterface::class)->reveal();
        $node = $this->prophesize(NodeInterface::class);
        $node->getAncestor(1)->willReturn($ancestor);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($ancestor, $suluNode->getAncestor(1));
    }

    public function testGetParent(): void
    {
        $parent = $this->prophesize(NodeInterface::class)->reveal();
        $node = $this->prophesize(NodeInterface::class);
        $node->getParent()->willReturn($parent);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($parent, $suluNode->getParent());
    }

    public function testGetDepth(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getDepth()->willReturn(1);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals(1, $suluNode->getDepth());
    }

    public function testGetSession(): void
    {
        $session = $this->prophesize(SessionInterface::class)->reveal();
        $node = $this->prophesize(NodeInterface::class);
        $node->getSession()->willReturn($session);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($session, $suluNode->getSession());
    }

    public function testIsNode(): void
    {
        $returnValue = true;
        $node = $this->prophesize(NodeInterface::class);
        $node->isNode()->willReturn($returnValue);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($returnValue, $suluNode->isNode());
    }

    public function testIsNew(): void
    {
        $returnValue = true;
        $node = $this->prophesize(NodeInterface::class);
        $node->isNew()->willReturn($returnValue);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($returnValue, $suluNode->isNew());
    }

    public function testIsModified(): void
    {
        $returnValue = true;
        $node = $this->prophesize(NodeInterface::class);
        $node->isModified()->willReturn($returnValue);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($returnValue, $suluNode->isModified());
    }

    public function testIsSame(): void
    {
        $returnValue = false;
        $otherNode = $this->prophesize(NodeInterface::class)->reveal();
        $node = $this->prophesize(NodeInterface::class);
        $node->isSame($otherNode)->willReturn($returnValue);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($returnValue, $suluNode->isSame($otherNode));
    }

    public function testAccept(): void
    {
        $visitor = $this->prophesize(ItemVisitorInterface::class)->reveal();
        $node = $this->prophesize(NodeInterface::class);
        $node->accept($visitor)->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->accept($visitor);
    }

    public function testRevert(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->revert()->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->revert();
    }

    public function testRemove(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->remove()->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->remove();
    }

    public function testAddNode(): void
    {
        $newNode = $this->prophesize(NodeInterface::class)->reveal();
        $node = $this->prophesize(NodeInterface::class);
        $node->addNode('test-1', 'sulu:content')->willReturn($newNode);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($newNode, $suluNode->addNode('test-1', 'sulu:content'));
    }

    public function testAddNodeAutoNamed(): void
    {
        $newNode = $this->prophesize(NodeInterface::class)->reveal();
        $node = $this->prophesize(NodeInterface::class);
        $node->addNodeAutoNamed('test-1', 'sulu:content')->willReturn($newNode);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($newNode, $suluNode->addNodeAutoNamed('test-1', 'sulu:content'));
    }

    public function testOrderBefore(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->orderBefore('test-1', 'test-2')->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->orderBefore('test-1', 'test-2');
    }

    public function testRename(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->rename('test-1')->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->rename('test-1');
    }

    public function testGetNode(): void
    {
        $otherNode = $this->prophesize(NodeInterface::class)->reveal();
        $node = $this->prophesize(NodeInterface::class);
        $node->getNode('test-1')->willReturn($otherNode);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($otherNode, $suluNode->getNode('test-1'));
    }

    public function testGetNodes(): void
    {
        $otherNodes = [$this->prophesize(NodeInterface::class)->reveal()];
        $node = $this->prophesize(NodeInterface::class);
        $node->getNodes('test-*', null)->willReturn($otherNodes);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($otherNodes, $suluNode->getNodes('test-*'));
    }

    public function testGetNodeNames(): void
    {
        $otherNodes = ['test-1'];
        $node = $this->prophesize(NodeInterface::class);
        $node->getNodeNames('test-*', null)->willReturn($otherNodes);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($otherNodes, $suluNode->getNodeNames('test-*'));
    }

    public function testGetProperty(): void
    {
        $property = $this->prophesize(PropertyInterface::class)->reveal();
        $node = $this->prophesize(NodeInterface::class);
        $node->getProperty('test-1')->willReturn($property);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($property, $suluNode->getProperty('test-1'));
    }

    public function testGetProperties(): void
    {
        $property = [
            $this->prophesize(PropertyInterface::class)->reveal(),
        ];
        $node = $this->prophesize(NodeInterface::class);
        $node->getProperties('test-*')->willReturn($property);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($property, $suluNode->getProperties('test-*'));
    }

    public function testGetPropertiesValues(): void
    {
        $values = [
            'test-1' => 'Some value',
        ];
        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertiesValues('test-*', false)->willReturn($values);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($values, $suluNode->getPropertiesValues('test-*', false));
    }

    public function testGetPropertyValue(): void
    {
        $value = 'Some value';
        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValue('test-1', PropertyType::DATE)->willReturn($value);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($value, $suluNode->getPropertyValue('test-1', PropertyType::DATE));
    }

    public function testGetPropertyValueWithoutType(): void
    {
        $value = 'Some value';
        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValue('test-1', PropertyType::UNDEFINED)->willReturn($value);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($value, $suluNode->getPropertyValue('test-1'));
    }

    public function testGetPropertyValueWithDefault(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getPropertyValueWithDefault('test-1', 'Default')->willReturn('Default');

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals('Default', $suluNode->getPropertyValueWithDefault('test-1', 'Default'));
    }

    public function testGetPrimaryItem(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getPrimaryItem()->willReturn('sulu:content');

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals('sulu:content', $suluNode->getPrimaryItem());
    }

    public function testGetIdentifier(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getIdentifier()->willReturn('123-123-123');

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals('123-123-123', $suluNode->getIdentifier());
    }

    public function testGetIndex(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getIndex()->willReturn(1);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals(1, $suluNode->getIndex());
    }

    public function testGetReferences(): void
    {
        $referencedNodes = [$this->prophesize(NodeInterface::class)->reveal()];
        $node = $this->prophesize(NodeInterface::class);
        $node->getReferences('sulu:content')->willReturn($referencedNodes);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($referencedNodes, $suluNode->getReferences('sulu:content'));
    }

    public function testGetWeakReferences(): void
    {
        $referencedNodes = [$this->prophesize(NodeInterface::class)->reveal()];
        $node = $this->prophesize(NodeInterface::class);
        $node->getWeakReferences('sulu:content')->willReturn($referencedNodes);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($referencedNodes, $suluNode->getWeakReferences('sulu:content'));
    }

    public function testHasNode(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->hasNode('test-1')->willReturn(true);

        $suluNode = new SuluNode($node->reveal());

        $this->assertTrue($suluNode->hasNode('test-1'));
    }

    public function testHasProperty(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperty('test-1')->willReturn(true);

        $suluNode = new SuluNode($node->reveal());

        $this->assertTrue($suluNode->hasProperty('test-1'));
    }

    public function testHasNodes(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->hasNodes()->willReturn(true);

        $suluNode = new SuluNode($node->reveal());

        $this->assertTrue($suluNode->hasNodes());
    }

    public function testHasProperties(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->hasProperties()->willReturn(true);

        $suluNode = new SuluNode($node->reveal());

        $this->assertTrue($suluNode->hasProperties());
    }

    public function testGetPrimaryNodeType(): void
    {
        $nodeType = $this->prophesize(NodeTypeInterface::class)->reveal();
        $node = $this->prophesize(NodeInterface::class);
        $node->getPrimaryNodeType()->willReturn($nodeType);

        $suluNode = new SuluNode($node->reveal());

        $this->assertSame($nodeType, $suluNode->getPrimaryNodeType());
    }

    public function testGetMixinNodeTypes(): void
    {
        $nodeTypes = [$this->prophesize(NodeTypeInterface::class)->reveal()];
        $node = $this->prophesize(NodeInterface::class);
        $node->getMixinNodeTypes()->willReturn($nodeTypes);

        $suluNode = new SuluNode($node->reveal());

        $this->assertSame($nodeTypes, $suluNode->getMixinNodeTypes());
    }

    public function testIsNodeType(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->isNodeType('sulu:content')->willReturn(true);

        $suluNode = new SuluNode($node->reveal());

        $this->assertTrue($suluNode->isNodeType('sulu:content'));
    }

    public function testSetPrimaryType(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->setPrimaryType('sulu:content')->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->setPrimaryType('sulu:content');
    }

    public function testAddMixin(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->addMixin('sulu:content')->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->addMixin('sulu:content');
    }

    public function testRemoveMixin(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->removeMixin('sulu:content')->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->removeMixin('sulu:content');
    }

    public function testSetMixins(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->setMixins(['sulu:content'])->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->setMixins(['sulu:content']);
    }

    public function testUpdate(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->update('default')->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->update('default');
    }

    public function testGetCorrespondingNodePath(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getCorrespondingNodePath('default')->willReturn('/test-1');

        $suluNode = new SuluNode($node->reveal());

        $this->assertSame('/test-1', $suluNode->getCorrespondingNodePath('default'));
    }

    public function testGetSharedSet(): void
    {
        $nodes = [];
        $node = $this->prophesize(NodeInterface::class);
        $node->getSharedSet()->willReturn($nodes);

        $suluNode = new SuluNode($node->reveal());

        $this->assertSame($nodes, [...$suluNode->getSharedSet()]);
    }

    public function testRemoveSharedSet(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->removeSharedSet()->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->removeSharedSet();
    }

    public function testRemoveShare(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->removeShare()->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->removeShare();
    }

    public function testIsCheckedOut(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->isCheckedOut()->willReturn(true);

        $suluNode = new SuluNode($node->reveal());

        $this->assertTrue($suluNode->isCheckedOut());
    }

    public function testIsLocked(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->isLocked()->willReturn(true);

        $suluNode = new SuluNode($node->reveal());

        $this->assertTrue($suluNode->isLocked());
    }

    public function testFollowLifecycleTransition(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->followLifecycleTransition('test-1')->shouldBeCalled();

        $suluNode = new SuluNode($node->reveal());

        $suluNode->followLifecycleTransition('test-1');
    }

    public function testGetAllowedLifecycleTransitions(): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $node->getAllowedLifecycleTransitions()->willReturn(['test-1']);

        $suluNode = new SuluNode($node->reveal());

        $this->assertSame(['test-1'], $suluNode->getAllowedLifecycleTransitions());
    }

    public function testGetIterator(): void
    {
        $node = $this->prophesize(NodeInterface::class)->willImplement(\IteratorAggregate::class);
        $node->getIterator()->willReturn($this->prophesize(\Iterator::class)->reveal());

        $suluNode = new SuluNode($node->reveal());

        $this->assertInstanceOf(\Iterator::class, $suluNode->getIterator());
    }

    public function testSetProperty(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $node = $this->prophesize(NodeInterface::class)->willImplement(\IteratorAggregate::class);

        $node->setProperty('test-1', 'test', PropertyType::STRING)->willReturn($property->reveal());
        $node->getPropertyValueWithDefault('test-1', null)->willReturn(null);

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($property->reveal(), $suluNode->setProperty('test-1', 'test', PropertyType::STRING));
    }

    public function testSetPropertySameType(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $node = $this->prophesize(NodeInterface::class)->willImplement(\IteratorAggregate::class);

        $node->setProperty('test-1', 'test', PropertyType::STRING)->willReturn($property->reveal());
        $node->getPropertyValueWithDefault('test-1', null)->willReturn('same-type');

        $suluNode = new SuluNode($node->reveal());

        $this->assertEquals($property->reveal(), $suluNode->setProperty('test-1', 'test', PropertyType::STRING));
    }

    public function testSetPropertyDifferentType(): void
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
