<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Subscriber\SecuritySubscriber;
use Sulu\Component\Content\Document\Subscriber\StructureSubscriber;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;

class SecuritySubscriberTest extends SubscriberTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SessionInterface>
     */
    private $liveSession;

    /**
     * @var SecuritySubscriber
     */
    private $subscriber;

    /**
     * @var ObjectProphecy<PropertyEncoder>
     */
    private $propertyEncoder;

    /**
     * @var ObjectProphecy<AccessControlManagerInterface>
     */
    private $accessControlManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->liveSession = $this->prophesize(SessionInterface::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->accessControlManager = $this->prophesize(AccessControlManagerInterface::class);

        $this->node->getProperties(Argument::cetera())->willReturn([]);

        $this->subscriber = new SecuritySubscriber(
            ['view' => 64, 'add' => 32, 'edit' => 16, 'delete' => 8],
            $this->liveSession->reveal(),
            $this->propertyEncoder->reveal(),
            $this->accessControlManager->reveal()
        );
    }

    public function testPersist(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('sec:role-1');
        $this->node->getProperties('sec:role-*')->willReturn([$property->reveal()]);
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveProperty = $this->prophesize(PropertyInterface::class);
        $liveNode->getProperty('sec:role-2')->willReturn($liveProperty->reveal());

        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->willImplement(PathBehavior::class);
        $document->getPath()->willReturn('/some/path');
        $document->getPermissions()->willReturn(
            [1 => ['view' => true, 'add' => true, 'edit' => true, 'delete' => false]]
        );

        $this->liveSession->getNode('/some/path')->willReturn($liveNode->reveal());

        $this->persistEvent->getDocument()->willReturn($document);

        $this->node->setProperty('sec:role-1', ['view', 'add', 'edit'])->shouldBeCalled();
        $property->remove()->shouldNotBeCalled();

        $liveNode->setProperty('sec:role-1', ['view', 'add', 'edit'])->shouldBeCalled();
        $liveProperty->remove()->shouldNotBeCalled();

        $this->node->setProperty('sec:permissions', '{"1":["view","add","edit"]}')->shouldBeCalled();
        $liveNode->setProperty('sec:permissions', '{"1":["view","add","edit"]}')->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testPersistWithoutPath(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('sec:role-1');
        $this->node->getProperties('sec:role-*')->willReturn([$property->reveal()]);

        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->willImplement(PathBehavior::class);
        $document->getPath()->willReturn(null);
        $document->getPermissions()->willReturn(
            [1 => ['view' => true, 'add' => true, 'edit' => true, 'delete' => false]]
        );

        $this->liveSession->getNode(Argument::any())->shouldNotBeCalled();

        $this->persistEvent->getDocument()->willReturn($document);

        $this->node->setProperty('sec:role-1', ['view', 'add', 'edit'])->shouldBeCalled();
        $property->remove()->shouldNotBeCalled();

        $this->node->setProperty('sec:permissions', '{"1":["view","add","edit"]}')->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testPersistWithDeletingRoles(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('sec:role-2');
        $this->node->getProperties('sec:role-*')->willReturn([$property->reveal()]);
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveProperty = $this->prophesize(PropertyInterface::class);
        $liveNode->getProperty('sec:role-2')->willReturn($liveProperty->reveal());
        $liveNode->hasProperty('sec:role-2')->willReturn(true);

        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->willImplement(PathBehavior::class);
        $document->getPath()->willReturn('/some/path');
        $document->getPermissions()->willReturn(
            [1 => ['view' => true, 'add' => true, 'edit' => true, 'delete' => false]]
        );

        $this->liveSession->getNode('/some/path')->willReturn($liveNode->reveal());

        $this->persistEvent->getDocument()->willReturn($document);

        $this->node->setProperty('sec:role-1', ['view', 'add', 'edit'])->shouldBeCalled();
        $property->remove()->shouldBeCalled();

        $liveNode->setProperty('sec:role-1', ['view', 'add', 'edit'])->shouldBeCalled();
        $liveProperty->remove()->shouldBeCalled();

        $this->node->setProperty('sec:permissions', '{"1":["view","add","edit"]}')->shouldBeCalled();
        $liveNode->setProperty('sec:permissions', '{"1":["view","add","edit"]}')->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testPersistWithoutAnyRoles(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $property->getName()->willReturn('sec:role-2');
        $this->node->getProperties('sec:role-*')->willReturn([$property->reveal()]);
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveProperty = $this->prophesize(PropertyInterface::class);
        $liveNode->getProperty('sec:role-2')->willReturn($liveProperty->reveal());
        $liveNode->hasProperty('sec:role-2')->willReturn(true);

        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->willImplement(PathBehavior::class);
        $document->getPath()->willReturn('/some/path');
        $document->getPermissions()->willReturn([]);

        $this->liveSession->getNode('/some/path')->willReturn($liveNode->reveal());

        $this->persistEvent->getDocument()->willReturn($document);

        $property->remove()->shouldBeCalled();
        $liveProperty->remove()->shouldBeCalled();

        $this->node->setProperty('sec:permissions', '[]')->shouldBeCalled();
        $liveNode->setProperty('sec:permissions', '[]')->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testPersistCreate(): void
    {
        $this->propertyEncoder
             ->encode('system_localized', StructureSubscriber::STRUCTURE_TYPE_FIELD, '*')
             ->willReturn('i18n:*-type');

        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->getPermissions()->willReturn();
        $document->setPermissions(
            [1 => ['view' => true, 'add' => true, 'edit' => true, 'delete' => false]]
        )->shouldBeCalled();

        $this->node->getProperties('i18n:*-type')->willReturn([]);

        $parentNode = $this->prophesize(NodeInterface::class);
        $parentNode->getIdentifier()->willReturn('parent-uuid');

        $this->accessControlManager->getPermissions(SecurityBehavior::class, 'parent-uuid')->willReturn(
            [1 => ['view' => true, 'add' => true, 'edit' => true, 'delete' => false]]
        );

        $this->persistEvent->hasParentNode()->willReturn(true);
        $this->persistEvent->getParentNode()->willReturn($parentNode->reveal());
        $this->persistEvent->getDocument()->willReturn($document);

        $this->subscriber->handlePersistCreate($this->persistEvent->reveal());
    }

    public function testPersistCreateForExistingDocument(): void
    {
        $this->propertyEncoder
             ->encode('system_localized', StructureSubscriber::STRUCTURE_TYPE_FIELD, '*')
             ->willReturn('i18n:*-type');

        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->getPermissions()->willReturn();
        $document->setPermissions(Argument::cetera())->shouldNotBeCalled();

        $property = $this->prophesize(PropertyInterface::class);
        $this->node->getProperties('i18n:*-type')->willReturn([$property->reveal()]);

        $this->persistEvent->hasParentNode()->willReturn(true);
        $this->persistEvent->getDocument()->willReturn($document);

        $this->subscriber->handlePersistCreate($this->persistEvent->reveal());
    }

    public function testPersistCreateForDocumentWithoutParentNode(): void
    {
        $this->propertyEncoder
             ->encode('system_localized', StructureSubscriber::STRUCTURE_TYPE_FIELD, '*')
             ->willReturn('i18n:*-type');

        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->getPermissions()->willReturn();
        $document->setPermissions(Argument::cetera())->shouldNotBeCalled();

        $this->node->getProperties('i18n:*-type')->willReturn([]);

        $this->persistEvent->hasParentNode()->willReturn(false);
        $this->persistEvent->getDocument()->willReturn($document);

        $this->subscriber->handlePersistCreate($this->persistEvent->reveal());
    }

    public function testPersistCreateForDocumentWithPermissions(): void
    {
        $this->propertyEncoder
             ->encode('system_localized', StructureSubscriber::STRUCTURE_TYPE_FIELD, '*')
             ->willReturn('i18n:*-type');

        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $document->getPermissions()->willReturn([1 => ['view' => true]]);
        $document->setPermissions(Argument::cetera())->shouldNotBeCalled();

        $this->node->getProperties('i18n:*-type')->willReturn([]);

        $this->persistEvent->hasParentNode()->willReturn(true);
        $this->persistEvent->getDocument()->willReturn($document);

        $this->subscriber->handlePersistCreate($this->persistEvent->reveal());
    }

    public function testHydrate(): void
    {
        /** @var SecurityBehavior $document */
        $document = $this->prophesize(SecurityBehavior::class);
        $node = $this->prophesize(NodeInterface::class);

        $node->hasProperty('sec:permissions')
            ->willReturn(true)
            ->shouldBeCalled();

        $node->getPropertyValue('sec:permissions')
            ->willReturn('{
                "1": ["view", "add", "edit"],
                "2": ["view", "edit"]
            }')
            ->shouldBeCalled();

        $this->hydrateEvent->getDocument()->willReturn($document);
        $this->hydrateEvent->getNode()->willReturn($node);

        $document->setPermissions([
            1 => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => false,
            ],
            2 => [
                'view' => true,
                'add' => false,
                'edit' => true,
                'delete' => false,
            ],
        ])->shouldBeCalled();

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }
}
