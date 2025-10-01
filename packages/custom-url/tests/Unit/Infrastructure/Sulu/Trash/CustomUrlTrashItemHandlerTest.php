<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Tests\Unit\Infrastructure\Sulu\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\CustomUrl\Domain\Model\CustomUrl;
use Sulu\CustomUrl\Infrastructure\Sulu\Trash\CustomUrlTrashItemHandler;

class CustomUrlTrashItemHandlerTest extends SuluTestCase
{
    private EntityManagerInterface $entityManager;

    private CustomUrlTrashItemHandler $customUrlTrashItemHandler;

    public function setUp(): void
    {
        static::purgeDatabase();

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);

        if (!$container->has(CustomUrlTrashItemHandler::class)) {
            $this->markTestSkipped('Enable the TrashBundle to run this test');
        }
        /** @var CustomUrlTrashItemHandler $trashItemHandler */
        $trashItemHandler = $container->get(CustomUrlTrashItemHandler::class);
        $this->customUrlTrashItemHandler = $trashItemHandler;
    }

    public function testStoreAndRestore(): void
    {
        $originalCustomUrl = new CustomUrl();
        $originalCustomUrlUuid = $originalCustomUrl->getUuid();
        $originalCustomUrl->setTitle('test-title-1');
        $originalCustomUrl->setWebspace('sulu_io');
        $originalCustomUrl->setCreated(new \DateTimeImmutable('2025-04-20T00:00:00+00:00'));
        $originalCustomUrl->setChanged(new \DateTimeImmutable('2025-04-20T00:00:00+00:00'));
        $originalCustomUrl->setBaseDomain('sulu-test.localhost/*/*');
        $originalCustomUrl->setDomainParts(['custom-path-1', 'custom-path-2']);
        $originalCustomUrl->setCanonical(true);
        $originalCustomUrl->setRedirect(false);
        $originalCustomUrl->setNoFollow(true);
        $originalCustomUrl->setNoIndex(false);
        $originalCustomUrl->setTargetDocument('23232323');
        $originalCustomUrl->setTargetLocale('de');
        $originalCustomUrl->setPublished(true);

        $trashItem = $this->customUrlTrashItemHandler->store($originalCustomUrl);
        $this->entityManager->flush();
        $this->entityManager->clear();

        static::assertSame($originalCustomUrlUuid, $trashItem->getResourceId());
        static::assertSame('test-title-1', $trashItem->getResourceTitle());
        static::assertSame('sulu.webspaces.sulu_io.custom-urls', $trashItem->getResourceSecurityContext());

        $restoreData = $trashItem->getRestoreData();
        static::assertSame('test-title-1', $restoreData['title']);
        static::assertSame(true, $restoreData['published']);
        static::assertSame('sulu-test.localhost/*/*', $restoreData['baseDomain']);
        static::assertSame(['custom-path-1', 'custom-path-2'], $restoreData['domainParts']);
        static::assertNull($restoreData['creator']);
        static::assertNull($restoreData['changer']);

        /** @var CustomUrl $restoredCustomUrl */
        $restoredCustomUrl = $this->customUrlTrashItemHandler->restore($trashItem, []);

        static::assertSame('test-title-1', $restoredCustomUrl->getTitle());
        static::assertSame($originalCustomUrl->getUuid(), $restoredCustomUrl->getUuid());
        static::assertSame('sulu_io', $restoredCustomUrl->getWebspace());
        static::assertSame('2025-04-20T00:00:00+00:00', $restoredCustomUrl->getCreated()->format('c'));
        static::assertNotNull($restoredCustomUrl->getChanged());
        static::assertSame('sulu-test.localhost/*/*', $restoredCustomUrl->getBaseDomain());
        static::assertSame(['custom-path-1', 'custom-path-2'], $restoredCustomUrl->getDomainParts());
        static::assertTrue($restoredCustomUrl->isCanonical());
        static::assertFalse($restoredCustomUrl->isRedirect());
        static::assertTrue($restoredCustomUrl->isNoFollow());
        static::assertFalse($restoredCustomUrl->isNoIndex());
        static::assertSame('23232323', $restoredCustomUrl->getTargetDocument());
        static::assertSame('de', $restoredCustomUrl->getTargetLocale());
        static::assertTrue($restoredCustomUrl->isPublished());
        static::assertNull($restoredCustomUrl->getCreator());
        static::assertNull($restoredCustomUrl->getChanger());
    }
}
