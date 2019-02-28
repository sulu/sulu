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

use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagManager;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
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
     * @var TagManagerInterface
     */
    private $tagManager;

    public function setUp()
    {
        $this->tagRepository = $this->getMockForAbstractClass(
            'Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface',
            [],
            '',
            false,
            true,
            true,
            ['findTagByName']
        );

        $this->fieldDescriptorFactory = $this->getMockForAbstractClass(
            'Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface',
            [],
            '',
            false
        );

        $this->em = $this->getMockForAbstractClass(
            'Doctrine\Common\Persistence\ObjectManager',
            [],
            '',
            false
        );

        $this->eventDispatcher = $this->getMockForAbstractClass(
            'Symfony\Component\EventDispatcher\EventDispatcherInterface',
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
            $this->eventDispatcher
        );
    }

    public function testResolveTagNames()
    {
        $tagNames = ['Tag1', 'Tag2', 'Tag3', 'InvalidTag'];

        $tagIds = $this->tagManager->resolveTagNames($tagNames);

        $this->assertEquals([1, 2, 3], $tagIds);
    }

    public function testResolveTagIds()
    {
        $tagIds = [1, 2, 3, 99];

        $tagNames = $this->tagManager->resolveTagIds($tagIds);

        $this->assertEquals(['Tag1', 'Tag2', 'Tag3'], $tagNames);
    }
}
