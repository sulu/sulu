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

namespace Sulu\Content\Tests\Functional\Application\ContentWorkflow;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\Kernel;
use Sulu\Content\Tests\Traits\ConsumeTransportTrait;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * With the message routed to a transport the validator's row stays pending until it is consumed.
 * Two approvals with one validator, so the validator decides whether the single reviewer is enough.
 */
#[CoversNothing]
class WorkflowTransitionRequestAsyncValidationTest extends SuluTestCase
{
    use ConsumeTransportTrait;
    use WorkflowTransitionRequestTrait;

    private const CONFIGURED_TEMPLATE = 'example-configured-workflow';

    private ContentManagerInterface $contentManager;

    private WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->contentManager = static::getContainer()->get(ContentManagerInterface::class);
        $this->workflowTransitionRequestRepository = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class);
        $this->authenticateAsRequestCreator('async-author');
    }

    public function testValidatorRowStaysPendingUntilTheMessageIsConsumed(): void
    {
        $example = $this->sendForReview($this->createExampleAtDraft(self::CONFIGURED_TEMPLATE));

        $request = $this->findRequest($example);
        $checks = $request->getDecisions();

        $this->assertCount(1, $checks);
        $this->assertSame('test_configured_result', $checks[0]->getValidatorKey());
        $this->assertSame(
            WorkflowTransitionRequestDecisionStatusEnum::PENDING,
            $checks[0]->getStatus(),
            'The validator must not have run inside the request.',
        );
        $this->assertCount(1, $this->transport()->getSent());

        $request->addApproval($this->createRequestCreator('async-reviewer'));
        static::getEntityManager()->flush();

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $this->resolveRequestStatus($request),
            'A check that has not answered yet counts as nothing, so one of the two approvals is missing.',
        );
    }

    public function testConsumingTheMessageStoresTheRejection(): void
    {
        $example = $this->sendForReview($this->createExampleAtDraft(self::CONFIGURED_TEMPLATE));

        $request = $this->findRequest($example);
        $request->addApproval($this->createRequestCreator('async-reviewer'));
        static::getEntityManager()->flush();

        $this->consumeTransport(Kernel::VALIDATION_TRANSPORT);

        static::getEntityManager()->clear();
        $request = $this->findRequest($example);
        $validatorReviewer = $request->getValidatorDecision('test_configured_result');

        $this->assertNotNull($validatorReviewer);
        $this->assertSame(WorkflowTransitionRequestDecisionStatusEnum::REJECTED, $validatorReviewer->getStatus());
        $this->assertSame(
            \sprintf('The configured check rejected example %s.', $example->getId()),
            $validatorReviewer->getMessages()[0]->text,
        );
        $this->assertNotNull($validatorReviewer->getDecidedAt());

        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $this->resolveRequestStatus($request),
            'A rejecting check does not count as an approval, so the second approval is still missing.',
        );
    }

    private function transport(): InMemoryTransport
    {
        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('messenger.transport.' . Kernel::VALIDATION_TRANSPORT);

        return $transport;
    }

    private function sendForReview(Example $example): Example
    {
        $this->contentManager->applyTransition(
            $example,
            ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'],
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        return $example;
    }

    private function findRequest(Example $example): WorkflowTransitionRequest
    {
        return $this->workflowTransitionRequestRepository->getOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]);
    }
}
