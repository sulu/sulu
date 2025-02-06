<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Functional\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Bundle\TagBundle\Trash\TagTrashItemHandler;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TagTrashItemHandlerTest extends SuluTestCase
{
    /**
     * @var TagTrashItemHandler
     */
    private $tagTrashItemHandler;

    /**
     * @var TagRepositoryInterface
     */
    private $tagRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function setUp(): void
    {
        static::purgeDatabase();
        $this->bootKernelAndSetServices();
    }

    public function bootKernelAndSetServices(): void
    {
        static::bootKernel();

        $this->tagTrashItemHandler = static::getContainer()->get('sulu_tag.tag_trash_item_handler');
        $this->tagRepository = static::getContainer()->get('sulu.repository.tag');
        $this->entityManager = static::getEntityManager();
    }

    public function testStoreAndRestore(): void
    {
        /** @var Tag $tag1 */
        $tag1 = $this->tagRepository->createNew();
        $tag1->setName('First Tag');
        $tag1->setCreated(new \DateTimeImmutable('2020-10-10'));
        $this->entityManager->persist($tag1);

        // create second tag to check if id of first tag is restored correctly
        $tag2 = $this->tagRepository->createNew();
        $tag2->setName('Second Tag');
        $this->entityManager->persist($tag2);

        $this->entityManager->flush();
        $originalTagId = $tag1->getId();
        static::assertCount(2, $this->entityManager->getRepository(TagInterface::class)->findAll());

        $trashItem = $this->tagTrashItemHandler->store($tag1);
        $this->entityManager->remove($tag1);
        $this->entityManager->flush();
        static::assertSame($originalTagId, (int) $trashItem->getResourceId());
        static::assertSame('First Tag', $trashItem->getResourceTitle());
        static::assertCount(1, $this->entityManager->getRepository(TagInterface::class)->findAll());

        // the TagTrashItemHandler::restore method changes the id generator for the entity to restore the original id
        // this works only if no entity of the same type was persisted before, because doctrine caches the insert sql
        // to clear the cached insert statement, we need to reboot the kernel of the application
        // this problem does not occur during normal usage because restoring is a separate request with a fresh kernel
        $this->bootKernelAndSetServices();

        /** @var TagInterface $restoredTag */
        $restoredTag = $this->tagTrashItemHandler->restore($trashItem, []);
        static::assertCount(2, $this->entityManager->getRepository(TagInterface::class)->findAll());
        static::assertSame($originalTagId, $restoredTag->getId());
        static::assertSame('First Tag', $restoredTag->getName());
        static::assertEquals(new \DateTimeImmutable('2020-10-10'), $restoredTag->getCreated());
    }
}
