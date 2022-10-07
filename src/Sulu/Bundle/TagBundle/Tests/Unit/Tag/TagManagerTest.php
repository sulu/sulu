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
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagManager;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TagManagerTest extends TestCase
{
    /**
     * @var TagRepositoryInterface
     */
    protected $tagRepository;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    protected $fieldDescriptorFactory;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    /**
     * @var TrashManagerInterface
     */
    private $trashManager;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    public function setUp(): void
    {
        $this->tagRepository = $this->getMockForAbstractClass(
            TagRepositoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['findTagByName']
        );

        $this->fieldDescriptorFactory = $this->getMockForAbstractClass(
            FieldDescriptorFactoryInterface::class,
            [],
            '',
            false
        );

        $this->em = $this->getMockForAbstractClass(
            ObjectManager::class,
            [],
            '',
            false
        );

        $this->eventDispatcher = $this->getMockForAbstractClass(
            EventDispatcherInterface::class,
            [],
            '',
            false
        );

        $this->domainEventCollector = $this->getMockForAbstractClass(
            DomainEventCollectorInterface::class,
            [],
            '',
            false
        );

        $this->trashManager = $this->getMockForAbstractClass(
            TrashManagerInterface::class,
            [],
            '',
            false
        );

        $this->tagRepository->expects($this->any())->method('findTagByName')->will($this->returnValueMap(
            [
                ['Tag1', (new Tag())->setId(1)],
                ['Tag2', (new Tag())->setId(2)],
                ['Tag3', (new Tag())->setId(3)],
            ]
        )
        );

        $this->tagRepository->expects($this->any())->method('findTagById')->will($this->returnValueMap(
            [
                [1, (new Tag())->setName('Tag1')],
                [2, (new Tag())->setName('Tag2')],
                [3, (new Tag())->setName('Tag3')],
            ]
        )
        );

        $this->tagManager = new TagManager(
            $this->tagRepository,
            $this->em,
            $this->eventDispatcher,
            $this->domainEventCollector,
            $this->trashManager
        );
    }

    public function testResolveTagNames(): void
    {
        $tagNames = ['Tag1', 'Tag2', 'Tag3', 'InvalidTag'];

        $tagIds = $this->tagManager->resolveTagNames($tagNames);

        $this->assertEquals([1, 2, 3], $tagIds);
    }

    public function testResolveTagIds(): void
    {
        $tagIds = [1, 2, 3, 99];

        $tagNames = $this->tagManager->resolveTagIds($tagIds);

        $this->assertEquals(['Tag1', 'Tag2', 'Tag3'], $tagNames);
    }
}
