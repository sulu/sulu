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

namespace Sulu\Redirect\Tests\Functional\Infrastructure\Doctrine\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Redirect\Domain\Exception\RedirectNotFoundException;
use Sulu\Redirect\Domain\Model\Redirect;
use Sulu\Redirect\Domain\Model\RedirectInterface;
use Sulu\Redirect\Domain\Repository\RedirectRepositoryInterface;
use Sulu\Redirect\Infrastructure\Doctrine\Repository\RedirectRepository;
use Symfony\Component\Uid\Uuid;

#[CoversClass(RedirectRepository::class)]
class RedirectRepositoryTest extends SuluTestCase
{
    private RedirectRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();

        $this->repository = new RedirectRepository(self::getEntityManager());
    }

    public function testHostSpecificWinsOverNullHost(): void
    {
        $this->create('/a', null, '/generic');
        $this->create('/a', 'example.com', '/specific');

        $this->assertSame('/specific', $this->repository->findEnabledBySource('/A', 'EXAMPLE.com')?->getTarget());
        $this->assertSame('/generic', $this->repository->findEnabledBySource('/a', 'other.com')?->getTarget());
        $this->assertSame('/generic', $this->repository->findEnabledBySource('/a', null)?->getTarget());
    }

    public function testDisabledIsIgnored(): void
    {
        $this->create('/a', null, '/b', false);

        $this->assertNull($this->repository->findEnabledBySource('/a', 'x'));
    }

    public function testDuplicateNullHostRowsReturnOne(): void
    {
        $this->insertRaw('/a', null, 301, '/first');
        $this->insertRaw('/a', null, 301, '/second');

        $this->assertNotNull($this->repository->findEnabledBySource('/a', null));
    }

    public function testFindOneByDuplicateNullHostRowsReturnsOne(): void
    {
        $this->insertRaw('/a', null, 301, '/first');
        $this->insertRaw('/a', null, 301, '/second');

        $this->assertNotNull($this->repository->findOneBy(['source' => '/a', 'sourceHost' => null]));
    }

    public function testLegacyStatusCodeHydrates(): void
    {
        $id = $this->insertRaw('/a', null, 307, '/b');

        $this->assertSame(307, $this->repository->getOneBy(['id' => $id])->getStatusCode());
    }

    public function testGetOneByThrows(): void
    {
        $this->expectException(RedirectNotFoundException::class);

        $this->repository->getOneBy(['id' => 'missing']);
    }

    public function testFindOneByNullHostFilter(): void
    {
        $this->create('/a', null, '/b');
        $this->create('/a', 'x.com', '/b');

        $redirect = $this->repository->findOneBy(['source' => '/a', 'sourceHost' => null]);

        $this->assertNotNull($redirect);
        $this->assertNull($redirect->getSourceHost());
    }

    public function testFindByIdsAndEnabled(): void
    {
        $a = $this->create('/a', null, '/x');
        $b = $this->create('/b', null, '/x');
        $this->create('/c', null, '/x', false);

        $this->assertCount(2, [...$this->repository->findBy(['ids' => [$a->getId(), $b->getId()]])]);
        $this->assertCount(1, [...$this->repository->findBy(['enabled' => false])]);
    }

    public function testAddAndRemove(): void
    {
        $id = $this->create('/a', null, '/b')->getId();

        $redirect = $this->repository->getOneBy(['id' => $id]);
        $this->repository->remove($redirect);
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        $this->assertNull($this->repository->findOneBy(['id' => $id]));
    }

    private function create(string $source, ?string $sourceHost, string $target, bool $enabled = true): RedirectInterface
    {
        $redirect = $this->repository->createNew();
        $redirect->modify(301, $source, $sourceHost, $target);
        $redirect->setEnabled($enabled);
        $this->repository->add($redirect);
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        return $redirect;
    }

    private function insertRaw(string $source, ?string $sourceHost, int $statusCode, string $target): string
    {
        $entityManager = self::getEntityManager();
        $id = Uuid::v7()->toRfc4122();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $entityManager->getConnection()->insert(
            $entityManager->getClassMetadata(Redirect::class)->getTableName(),
            [
                'id' => $id,
                'enabled' => 1,
                'status_code' => $statusCode,
                'source' => $source,
                'source_host' => $sourceHost,
                'target' => $target,
                'created' => $now,
                'changed' => $now,
            ],
        );

        return $id;
    }
}
