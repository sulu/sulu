<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Unit\Tag;


use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagManager;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Component\Security\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TagManagerTest extends \PHPUnit_Framework_TestCase
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
            array(),
            '',
            false,
            true,
            true,
            array('findTagByName')
        );

        $this->userRepository = $this->getMockForAbstractClass(
            'Sulu\Component\Security\UserRepositoryInterface',
            array(),
            '',
            false
        );

        $this->em = $this->getMockForAbstractClass(
            'Doctrine\Common\Persistence\ObjectManager',
            array(),
            '',
            false
        );

        $this->eventDispatcher = $this->getMockForAbstractClass(
            'Symfony\Component\EventDispatcher\EventDispatcherInterface',
            array(),
            '',
            false
        );

        $this->tagRepository->expects($this->any())->method('findTagByName')->will($this->returnValueMap(
                array(
                    array('Tag1', (new Tag())->setId(1)),
                    array('Tag2', (new Tag())->setId(2)),
                    array('Tag3', (new Tag())->setId(3)),
                )
            )
        );

        $this->tagRepository->expects($this->any())->method('findTagById')->will($this->returnValueMap(
                array(
                    array(1, (new Tag())->setName('Tag1')),
                    array(2, (new Tag())->setName('Tag2')),
                    array(3, (new Tag())->setName('Tag3')),
                )
            )
        );

        $this->tagManager = new TagManager(
            $this->tagRepository,
            $this->userRepository,
            $this->em,
            $this->eventDispatcher
        );
    }

    public function testResolveTagNames()
    {
        $tagNames = array('Tag1', 'Tag2', 'Tag3', 'InvalidTag');

        $tagIds = $this->tagManager->resolveTagNames($tagNames);

        $this->assertEquals(array(1, 2, 3), $tagIds);
    }

    public function testResolveTagIds()
    {
        $tagIds = array(1, 2, 3, 99);

        $tagNames = $this->tagManager->resolveTagIds($tagIds);

        $this->assertEquals(array('Tag1', 'Tag2', 'Tag3'), $tagNames);
    }
}
