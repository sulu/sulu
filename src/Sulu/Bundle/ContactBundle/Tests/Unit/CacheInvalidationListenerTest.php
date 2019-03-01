<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Prophecy\Argument;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\EventListener\CacheInvalidationListener;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Contact\Model\ContactInterface;
use Sulu\Component\HttpCache\HandlerInvalidateReferenceInterface;

class CacheInvalidationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HandlerInvalidateReferenceInterface
     */
    private $invalidationHandler;

    /**
     * @var CacheInvalidationListener
     */
    private $listener;

    protected function setUp()
    {
        $this->invalidationHandler = $this->prophesize(HandlerInvalidateReferenceInterface::class);

        $this->listener = new CacheInvalidationListener($this->invalidationHandler->reveal());
    }

    public function provideData()
    {
        return [
            [ContactInterface::class, 'contact'],
            [AccountInterface::class, 'account'],
            [\stdClass::class, null],
        ];
    }

    /**
     * @dataProvider provideData
     */
    public function testPostPersist($class, $alias)
    {
        $entity = $this->prophesize($class);

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        if ($alias) {
            $entity->getId()->willReturn(1);
            $entity->getTags()->willReturn([]);
            $entity->getCategories()->willReturn([]);

            $this->invalidationHandler->invalidateReference($alias, 1)->shouldBeCalled();
        } else {
            $this->invalidationHandler->invalidateReference(Argument::cetera())->shouldNotBeCalled();
        }

        $this->listener->postPersist($eventArgs->reveal());
    }

    /**
     * @dataProvider provideData
     */
    public function testPostUpdate($class, $alias)
    {
        $entity = $this->prophesize($class);

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        if ($alias) {
            $entity->getId()->willReturn(1);
            $entity->getTags()->willReturn([]);
            $entity->getCategories()->willReturn([]);

            $this->invalidationHandler->invalidateReference($alias, 1)->shouldBeCalled();
        } else {
            $this->invalidationHandler->invalidateReference(Argument::cetera())->shouldNotBeCalled();
        }

        $this->listener->postUpdate($eventArgs->reveal());
    }

    /**
     * @dataProvider provideData
     */
    public function testPreRemove($class, $alias)
    {
        $entity = $this->prophesize($class);

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        if ($alias) {
            $entity->getId()->willReturn(1);
            $entity->getTags()->willReturn([]);
            $entity->getCategories()->willReturn([]);

            $this->invalidationHandler->invalidateReference($alias, 1)->shouldBeCalled();
        } else {
            $this->invalidationHandler->invalidateReference(Argument::cetera())->shouldNotBeCalled();
        }

        $this->listener->preRemove($eventArgs->reveal());
    }

    public function provideDataWithTagsAndCategories()
    {
        return [
            [ContactInterface::class, 'contact'],
            [AccountInterface::class, 'account'],
        ];
    }

    /**
     * @dataProvider provideDataWithTagsAndCategories
     */
    public function testPersistUpdateWithTagsAndCategories($class, $alias)
    {
        $entity = $this->prophesize($class);
        $entity->getId()->willReturn(1);

        $tags = [$this->prophesize(TagInterface::class), $this->prophesize(TagInterface::class)];
        $tags[0]->getId()->willReturn(1);
        $tags[1]->getId()->willReturn(2);
        $entity->getTags()->willReturn($tags);
        $this->invalidationHandler->invalidateReference('tag', 1)->shouldBeCalled();
        $this->invalidationHandler->invalidateReference('tag', 2)->shouldBeCalled();

        $categories = [$this->prophesize(CategoryInterface::class), $this->prophesize(CategoryInterface::class)];
        $categories[0]->getId()->willReturn(1);
        $categories[1]->getId()->willReturn(2);
        $entity->getCategories()->willReturn($categories);
        $this->invalidationHandler->invalidateReference('category', 1)->shouldBeCalled();
        $this->invalidationHandler->invalidateReference('category', 2)->shouldBeCalled();

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        $this->invalidationHandler->invalidateReference($alias, 1)->shouldBeCalled();

        $this->listener->postPersist($eventArgs->reveal());
    }

    /**
     * @dataProvider provideDataWithTagsAndCategories
     */
    public function testPostUpdateWithTagsAndCategories($class, $alias)
    {
        $entity = $this->prophesize($class);
        $entity->getId()->willReturn(1);

        $tags = [$this->prophesize(TagInterface::class), $this->prophesize(TagInterface::class)];
        $tags[0]->getId()->willReturn(1);
        $tags[1]->getId()->willReturn(2);
        $entity->getTags()->willReturn($tags);
        $this->invalidationHandler->invalidateReference('tag', 1)->shouldBeCalled();
        $this->invalidationHandler->invalidateReference('tag', 2)->shouldBeCalled();

        $categories = [$this->prophesize(CategoryInterface::class), $this->prophesize(CategoryInterface::class)];
        $categories[0]->getId()->willReturn(1);
        $categories[1]->getId()->willReturn(2);
        $entity->getCategories()->willReturn($categories);
        $this->invalidationHandler->invalidateReference('category', 1)->shouldBeCalled();
        $this->invalidationHandler->invalidateReference('category', 2)->shouldBeCalled();

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        $this->invalidationHandler->invalidateReference($alias, 1)->shouldBeCalled();

        $this->listener->postUpdate($eventArgs->reveal());
    }

    /**
     * @dataProvider provideDataWithTagsAndCategories
     */
    public function testPreRemoveUpdateWithTagsAndCategories($class, $alias)
    {
        $entity = $this->prophesize($class);
        $entity->getId()->willReturn(1);

        $tags = [$this->prophesize(TagInterface::class), $this->prophesize(TagInterface::class)];
        $tags[0]->getId()->willReturn(1);
        $tags[1]->getId()->willReturn(2);
        $entity->getTags()->willReturn($tags);
        $this->invalidationHandler->invalidateReference('tag', 1)->shouldBeCalled();
        $this->invalidationHandler->invalidateReference('tag', 2)->shouldBeCalled();

        $categories = [$this->prophesize(CategoryInterface::class), $this->prophesize(CategoryInterface::class)];
        $categories[0]->getId()->willReturn(1);
        $categories[1]->getId()->willReturn(2);
        $entity->getCategories()->willReturn($categories);
        $this->invalidationHandler->invalidateReference('category', 1)->shouldBeCalled();
        $this->invalidationHandler->invalidateReference('category', 2)->shouldBeCalled();

        $eventArgs = $this->prophesize(LifecycleEventArgs::class);
        $eventArgs->getObject()->willReturn($entity->reveal());

        $this->invalidationHandler->invalidateReference($alias, 1)->shouldBeCalled();

        $this->listener->preRemove($eventArgs->reveal());
    }
}
