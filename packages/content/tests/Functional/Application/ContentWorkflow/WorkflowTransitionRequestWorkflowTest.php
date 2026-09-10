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
use Sulu\Content\Domain\Exception\DuplicateActiveWorkflowTransitionRequestException;
use Sulu\Content\Domain\Exception\NoRequestWorkflowException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;

#[CoversNothing]
class WorkflowTransitionRequestWorkflowTest extends SuluTestCase
{
    use WorkflowTransitionRequestTrait;

    private ContentManagerInterface $contentManager;

    private WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->contentManager = static::getContainer()->get(ContentManagerInterface::class);
        $this->workflowTransitionRequestRepository = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class);
        $this->authenticateAsRequestCreator();
    }

    public function testRequestForReviewDraftCreatesPendingRequest(): void
    {
        $example = $this->createExampleAtDraft();

        $this->contentManager->applyTransition(
            $example,
            ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'],
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $request = $this->workflowTransitionRequestRepository->findOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]);

        $this->assertNotNull($request);
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PENDING, $this->resolveRequestStatus($request));
    }

    public function testRequestForReviewWhenActiveExistsThrowsDuplicate(): void
    {
        $example = $this->createExampleAtDraft();

        $activeRequest = new WorkflowTransitionRequest(Example::RESOURCE_KEY, (string) $example->getId(), 'en', 'default');
        $this->workflowTransitionRequestRepository->add($activeRequest);
        static::getEntityManager()->flush();

        $this->expectException(DuplicateActiveWorkflowTransitionRequestException::class);

        $this->contentManager->applyTransition(
            $example,
            ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'],
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
    }

    /**
     * Whether a caller may publish at all is the authorizer's answer; it never reaches the workflow.
     */
    public function testPublishWithoutARequestIsNotBlocked(): void
    {
        $example = $this->createExampleAtDraft();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        $dimensionContent = $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
        );
        static::getEntityManager()->flush();

        $this->assertSame(WorkflowInterface::WORKFLOW_PLACE_PUBLISHED, $dimensionContent->getWorkflowPlace());
    }

    /**
     * The workflow has no view on permissions, so an open request does not stop `publish` here.
     */
    public function testPublishWithPendingRequestClosesTheRequest(): void
    {
        $example = $this->createExampleAtDraft();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $dimensionContent = $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
        );
        static::getEntityManager()->flush();

        $this->assertSame(WorkflowInterface::WORKFLOW_PLACE_PUBLISHED, $dimensionContent->getWorkflowPlace());

        $request = $this->workflowTransitionRequestRepository->findOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
        ]);

        $this->assertNotNull($request);
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PUBLISHED, $this->resolveRequestStatus($request));
    }

    public function testCancelReviewClosesTheRequestAndReturnsContentToUnpublished(): void
    {
        $example = $this->createExampleAtDraft();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        $this->contentManager->applyTransition($example, $dimensionAttributes, WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH);
        $this->contentManager->applyTransition($example, $dimensionAttributes, WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW);
        static::getEntityManager()->flush();

        $request = $this->workflowTransitionRequestRepository->getOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]);

        $dimensionContent = $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW,
        );
        static::getEntityManager()->flush();

        $this->assertSame(WorkflowInterface::WORKFLOW_PLACE_UNPUBLISHED, $dimensionContent->getWorkflowPlace());

        $cancelledRequest = $this->workflowTransitionRequestRepository->getOneBy(['id' => $request->getId()]);
        $this->assertSame(WorkflowTransitionRequestStatusEnum::CANCELLED, $this->resolveRequestStatus($cancelledRequest));
        $this->assertNull($cancelledRequest->getActiveKey(), 'A cancelled request must not block the next one.');
    }

    /**
     * A reviewer's rejection is a vote and leaves the request open, because the remaining approvals
     * can still carry it - see WorkflowTransitionRequestTest::testRejectionsDoNotBlockTheRequiredApprovals.
     * The `reject` content transition is the other thing: it takes the content out of review, and a
     * request left open behind it would lock the content with no transition able to free it again.
     */
    public function testRejectClosesTheRequest(): void
    {
        $example = $this->createExampleAtDraft();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        $this->contentManager->applyTransition($example, $dimensionAttributes, WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH);
        $this->contentManager->applyTransition($example, $dimensionAttributes, WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW);
        static::getEntityManager()->flush();

        $request = $this->workflowTransitionRequestRepository->getOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]);

        $dimensionContent = $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_REJECT,
        );
        static::getEntityManager()->flush();

        $this->assertSame(WorkflowInterface::WORKFLOW_PLACE_UNPUBLISHED, $dimensionContent->getWorkflowPlace());

        $rejectedRequest = $this->workflowTransitionRequestRepository->getOneBy(['id' => $request->getId()]);
        $this->assertSame(WorkflowTransitionRequestStatusEnum::CANCELLED, $this->resolveRequestStatus($rejectedRequest));
        $this->assertNull($rejectedRequest->getActiveKey(), 'A rejected request must not block the next one.');
    }

    public function testCancelReviewDraftClosesTheRequestAndReturnsContentToDraft(): void
    {
        $example = $this->createExampleAtDraft();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $request = $this->workflowTransitionRequestRepository->getOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]);

        $dimensionContent = $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $this->assertSame(WorkflowInterface::WORKFLOW_PLACE_DRAFT, $dimensionContent->getWorkflowPlace());

        $cancelledRequest = $this->workflowTransitionRequestRepository->getOneBy(['id' => $request->getId()]);
        $this->assertSame(WorkflowTransitionRequestStatusEnum::CANCELLED, $this->resolveRequestStatus($cancelledRequest));
        $this->assertNull($cancelledRequest->getActiveKey(), 'A cancelled request must not block the next one.');
    }

    /**
     * Someone other than the creator may cancel, which is how content gets back to its author.
     * The permission it needs is asserted in PageInReviewTest::testCancelIsForbiddenWithoutReviewPermission,
     * because a direct service call has no request context and the security checker then grants everything.
     */
    public function testCancelReviewDraftByAnotherUserClosesTheRequest(): void
    {
        $example = $this->createExampleAtDraft();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $request = $this->workflowTransitionRequestRepository->getOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]);

        $this->authenticateAsRequestCreator('reviewer_canceller');

        $dimensionContent = $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $this->assertSame(WorkflowInterface::WORKFLOW_PLACE_DRAFT, $dimensionContent->getWorkflowPlace());

        $cancelledRequest = $this->workflowTransitionRequestRepository->getOneBy(['id' => $request->getId()]);
        $this->assertSame(WorkflowTransitionRequestStatusEnum::CANCELLED, $this->resolveRequestStatus($cancelledRequest));
        $this->assertNull($cancelledRequest->getActiveKey(), 'A cancelled request must not block the next one.');
    }

    public function testRequestForReviewIsRefusedWhenTheTemplateOptsOut(): void
    {
        $example = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'example-no-workflow',
                        'title' => 'Published Title',
                        'url' => '/published-title-no-workflow',
                    ],
                    'draft' => [
                        'template' => 'example-no-workflow',
                        'title' => 'Draft Title',
                        'url' => '/draft-title-no-workflow',
                    ],
                ],
            ],
            ['create_route' => true],
        );
        static::getEntityManager()->flush();

        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        try {
            $this->contentManager->applyTransition(
                $example,
                $dimensionAttributes,
                WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
            );
            $this->fail('Expected the transition to be refused for a template tagged workflow="none".');
        } catch (NoRequestWorkflowException $exception) {
            $this->assertSame(
                'sulu_content.workflow_transition_request.no_workflow',
                $exception->getMessageTranslationKey(),
            );
        }

        $dimensionContent = $this->contentManager->resolve($example, $dimensionAttributes);
        $this->assertSame(
            WorkflowInterface::WORKFLOW_PLACE_DRAFT,
            $dimensionContent->getWorkflowPlace(),
            'A refused request must leave the content where it was.',
        );

        $this->assertNull($this->workflowTransitionRequestRepository->findOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]));
    }
}
