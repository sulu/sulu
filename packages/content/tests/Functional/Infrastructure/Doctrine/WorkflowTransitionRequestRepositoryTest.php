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

namespace Sulu\Content\Tests\Functional\Infrastructure\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecision;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;

class WorkflowTransitionRequestRepositoryTest extends SuluTestCase
{
    use WorkflowTransitionRequestTrait;

    private WorkflowTransitionRequestRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->repository = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class);
    }

    public function testSettleDecisionCountsOnlyTheChecksOfItsOwnRequest(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->addValidatorDecision('first');
        $request->addValidatorDecision('second');
        $request->addValidatorDecision('third');
        $request->addApproval($this->createRequestCreator('repository-reviewer'));

        $otherRequest = new WorkflowTransitionRequest('pages', 'res-2', 'en', 'default');
        $otherRequest->addValidatorDecision('first');

        $this->repository->add($request);
        $this->repository->add($otherRequest);
        static::getEntityManager()->flush();

        $this->assertSame(
            ['approved' => 1, 'rejected' => 0, 'pending' => 2],
            $this->repository->settleDecision($this->decision($request, 'first'), WorkflowTransitionRequestDecisionStatusEnum::APPROVED, []),
            'The reviewer\'s approval is not a check, and the other request\'s pending check is not ours.',
        );
        $this->assertSame(
            ['approved' => 1, 'rejected' => 1, 'pending' => 1],
            $this->repository->settleDecision($this->decision($request, 'second'), WorkflowTransitionRequestDecisionStatusEnum::REJECTED, []),
        );
        $this->assertSame(
            ['approved' => 2, 'rejected' => 1, 'pending' => 0],
            $this->repository->settleDecision($this->decision($request, 'third'), WorkflowTransitionRequestDecisionStatusEnum::APPROVED, []),
        );
        $this->assertSame(
            WorkflowTransitionRequestDecisionStatusEnum::PENDING,
            $this->decision($otherRequest, 'first')->getStatus(),
        );
    }

    public function testSettleDecisionOfAnAlreadySettledCheckReturnsNull(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->addValidatorDecision('first');

        $this->repository->add($request);
        static::getEntityManager()->flush();

        $decision = $this->decision($request, 'first');
        $this->assertNotNull($this->repository->settleDecision($decision, WorkflowTransitionRequestDecisionStatusEnum::APPROVED, []));
        $this->assertNull(
            $this->repository->settleDecision($decision, WorkflowTransitionRequestDecisionStatusEnum::REJECTED, []),
            'The first answer wins.',
        );
        $this->assertSame(WorkflowTransitionRequestDecisionStatusEnum::APPROVED, $decision->getStatus());
    }

    public function testSettleDecisionWaitsForTheLockOnTheRequest(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->addValidatorDecision('first');
        $request->addValidatorDecision('second');

        $this->repository->add($request);
        static::getEntityManager()->flush();

        $connection = static::getEntityManager()->getConnection();
        $platform = $connection->getDatabasePlatform();
        if ($platform instanceof AbstractMySQLPlatform) {
            $setTimeout = 'SET SESSION innodb_lock_wait_timeout = 1';
            $resetTimeout = 'SET SESSION innodb_lock_wait_timeout = DEFAULT';
        } elseif ($platform instanceof PostgreSQLPlatform) {
            $setTimeout = "SET lock_timeout = '1s'";
            $resetTimeout = 'RESET lock_timeout';
        } else {
            $this->markTestSkipped('The row lock is only observable on MySQL, MariaDB and PostgreSQL.');
        }

        $tableName = static::getEntityManager()->getClassMetadata(WorkflowTransitionRequest::class)->getTableName();
        $otherWorker = DriverManager::getConnection($connection->getParams()); // @phpstan-ignore argument.type
        $otherWorker->beginTransaction();

        try {
            $otherWorker->fetchOne(\sprintf('SELECT id FROM %s WHERE id = :id FOR UPDATE', $tableName), ['id' => $request->getId()]);
            $connection->executeStatement($setTimeout);

            try {
                $this->expectException(DriverException::class);
                $this->repository->settleDecision($this->decision($request, 'first'), WorkflowTransitionRequestDecisionStatusEnum::APPROVED, []);
            } finally {
                $connection->executeStatement($resetTimeout);
            }
        } finally {
            $this->release($otherWorker);
        }
    }

    private function release(Connection $connection): void
    {
        $connection->rollBack();
        $connection->close();
    }

    private function decision(WorkflowTransitionRequest $request, string $validatorKey): WorkflowTransitionRequestDecision
    {
        $decision = $request->getValidatorDecision($validatorKey);
        $this->assertNotNull($decision);

        return $decision;
    }
}
