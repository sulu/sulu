<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Tests\Functional\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\Domain\Exception\TrashItemNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Bundle\TrashBundle\Tests\Functional\Traits\CreateTrashItemTrait;

class TrashItemRepositoryTest extends SuluTestCase
{
    use CreateTrashItemTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TrashItemRepositoryInterface
     */
    private $repository;

    public function setUp(): void
    {
        static::purgeDatabase();

        $this->entityManager = static::getEntityManager();
        $this->repository = static::getTrashItemRepository();
    }

    public function testCreate(): void
    {
        $restoreData = ['foo' => 'bar'];

        $trashItem = $this->repository->create(
            'resourceKey',
            '1',
            $restoreData,
            'Unlocalized resource title',
            'sulu.settings.test_context',
            'TestClass',
            '1'
        );

        static::assertSame('resourceKey', $trashItem->getResourceKey());
        static::assertSame('1', $trashItem->getResourceId());
        static::assertSame($restoreData, $trashItem->getRestoreData());
        static::assertSame('Unlocalized resource title', $trashItem->getResourceTitle());
        static::assertSame('Unlocalized resource title', $trashItem->getResourceTitle('anything'));
        static::assertSame('sulu.settings.test_context', $trashItem->getResourceSecurityContext());
        static::assertSame('TestClass', $trashItem->getResourceSecurityObjectType());
        static::assertSame('1', $trashItem->getResourceSecurityObjectId());
        static::assertInstanceOf(\DateTimeImmutable::class, $trashItem->getTimestamp());
    }

    public function testCreateLocalizedTitles(): void
    {
        $trashItem = $this->repository->create(
            'resourceKey',
            '1',
            [],
            [
                'en' => 'English resource title',
                'de' => 'German resource title',
            ],
            null,
            null,
            null
        );

        static::assertSame('English resource title', $trashItem->getResourceTitle('en'));
        static::assertSame('German resource title', $trashItem->getResourceTitle('de'));
        static::assertSame('English resource title', $trashItem->getResourceTitle());
        static::assertSame('English resource title', $trashItem->getResourceTitle('other'));
    }

    public function testAdd(): void
    {
        $trashItem = $this->repository->create(
            'resourceKey',
            '1',
            ['foo' => 'bar'],
            'Resource title',
            null,
            null,
            null
        );

        $this->repository->add($trashItem);
        $this->entityManager->flush();

        $result = $this->repository->findOneBy(['id' => $trashItem->getId()]);

        static::assertNotNull($result);
    }

    public function testRemove(): void
    {
        $trashItem = static::createTrashItem(
            'test_resource',
            '1',
            [],
            'Resource title'
        );

        $this->repository->remove($trashItem);
        $this->entityManager->flush();

        $result = $this->repository->findOneBy(['id' => $trashItem->getId()]);

        static::assertNull($result);
    }

    public function testFindOneBy(): void
    {
        $trashItem = static::createTrashItem(
            'test_resource',
            '1',
            [],
            'Resource title'
        );

        $result = $this->repository->findOneBy(['id' => $trashItem->getId()]);

        static::assertNotNull($result);
    }

    public function testFindOneByNotFound(): void
    {
        $result = $this->repository->findOneBy(['id' => 'not-existing']);

        static::assertNull($result);
    }

    public function testGetOneBy(): void
    {
        $trashItem = static::createTrashItem(
            'test_resource',
            '1',
            [],
            'Resource title'
        );

        $result = $this->repository->getOneBy(['id' => $trashItem->getId()]);

        static::assertNotNull($result);
    }

    public function testGetOneByNotFound(): void
    {
        $this->expectException(TrashItemNotFoundException::class);

        $this->repository->getOneBy(['id' => 'not-existing']);
    }

    protected static function getTrashItemRepository(): TrashItemRepositoryInterface
    {
        return static::getContainer()->get('sulu_trash.trash_item_repository');
    }
}
