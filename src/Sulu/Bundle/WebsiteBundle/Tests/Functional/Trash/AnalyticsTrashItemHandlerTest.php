<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\WebsiteBundle\Entity\Analytics;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\Trash\AnalyticsTrashItemHandler;

class AnalyticsTrashItemHandlerTest extends SuluTestCase
{
    /**
     * @var AnalyticsTrashItemHandler
     */
    private $analyticsTrashItemHandler;

    /**
     * @var AnalyticsRepositoryInterface
     */
    private $analyticsRepository;

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

        $this->analyticsTrashItemHandler = static::getContainer()->get('sulu_website.analytics_trash_item_handler');
        $this->analyticsRepository = static::getContainer()->get('sulu.repository.analytics');
        $this->entityManager = static::getEntityManager();
    }

    public function testStoreAndRestore(): void
    {
        /** @var Analytics $analytics1 */
        $analytics1 = $this->analyticsRepository->createNew();
        $analytics1->setTitle('First Analytics');
        $analytics1->setType('custom');
        $analytics1->setWebspaceKey('sulu_io');
        $analytics1->setAllDomains(true);
        $analytics1->setContent([
            'position' => null,
            'value' => null,
        ]);
        $this->entityManager->persist($analytics1);

        // create second analytics to check if id of first analytics is restored correctly
        $analytics2 = $this->analyticsRepository->createNew();
        $analytics2->setTitle('Second Analytics');
        $analytics2->setType('custom');
        $analytics2->setWebspaceKey('sulu_io');
        $analytics2->setAllDomains(false);
        $analytics2->setContent([
            'position' => null,
            'value' => null,
        ]);
        $this->entityManager->persist($analytics2);

        $this->entityManager->flush();
        $originalAnalyticsId = $analytics1->getId();
        static::assertCount(2, $this->entityManager->getRepository(AnalyticsInterface::class)->findAll());

        $trashItem = $this->analyticsTrashItemHandler->store($analytics1);
        $this->entityManager->remove($analytics1);
        $this->entityManager->flush();
        static::assertSame($originalAnalyticsId, (int) $trashItem->getResourceId());
        static::assertSame('First Analytics', $trashItem->getResourceTitle());
        static::assertCount(1, $this->entityManager->getRepository(AnalyticsInterface::class)->findAll());

        // the AnalyticsTrashItemHandler::restore method changes the id generator for the entity to restore the original id
        // this works only if no entity of the same type was persisted before, because doctrine caches the insert sql
        // to clear the cached insert statement, we need to reboot the kernel of the application
        // this problem does not occur during normal usage because restoring is a separate request with a fresh kernel
        $this->bootKernelAndSetServices();

        /** @var AnalyticsInterface $restoredAnalytics */
        $restoredAnalytics = $this->analyticsTrashItemHandler->restore($trashItem, []);
        static::assertCount(2, $this->entityManager->getRepository(AnalyticsInterface::class)->findAll());
        static::assertSame($originalAnalyticsId, $restoredAnalytics->getId());
        static::assertSame('First Analytics', $restoredAnalytics->getTitle());
    }
}
