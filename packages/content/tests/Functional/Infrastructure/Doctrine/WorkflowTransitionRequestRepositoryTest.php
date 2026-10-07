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

    private function decision(WorkflowTransitionRequest $request, string $validatorKey): WorkflowTransitionRequestDecision
    {
        $decision = $request->getValidatorDecision($validatorKey);
        $this->assertNotNull($decision);

        return $decision;
    }
}
