<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Unit\Tag;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagManager;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TagManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TagRepositoryInterface>
     */
    protected $tagRepository;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var ObjectProphecy<ObjectManager>
     */
    protected $em;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    protected $eventDispatcher;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private $domainEventCollector;

    /**
     * @var ObjectProphecy<TrashManagerInterface>
     */
    private $trashManager;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    public function setUp(): void
    {
        $this->em = $this->prophesize(ObjectManager::class);

        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $this->trashManager = $this->prophesize(TrashManagerInterface::class);

        $this->tagRepository = $this->prophesize(TagRepositoryInterface::class);

        $this->tagManager = new TagManager(
            $this->tagRepository->reveal(),
            $this->em->reveal(),
            $this->eventDispatcher->reveal(),
            $this->domainEventCollector->reveal(),
            $this->trashManager->reveal()
        );
    }

    public function testFindById(): void
    {
        $tag = new Tag();
        $this->tagRepository->findTagById(10)->shouldBeCalled()->willReturn($tag);

        $actualTag = $this->tagManager->findById(10);

        $this->assertEquals($tag, $actualTag);
    }
}
