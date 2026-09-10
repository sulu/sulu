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

namespace Sulu\Content\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Verifies the template-tag-driven workflow resolution: a template that opts into a non-default
 * named workflow via `<tag name="sulu_content.request_workflow" workflow="strict"/>` produces a
 * request with `workflowName=strict`, governed by that workflow's `required_user_approvals`.
 */
#[CoversNothing]
class WorkflowTransitionRequestStrictWorkflowTest extends SuluTestCase
{
    use WorkflowTransitionRequestTrait;

    private KernelBrowser $client;

    private ContentManagerInterface $contentManager;

    private WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );

        self::purgeDatabase();

        $this->contentManager = static::getContainer()->get(ContentManagerInterface::class);
        $this->workflowTransitionRequestRepository = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class);

        $this->authenticateAsRequestCreator();
    }

    public function testRequestCreatedWithStrictWorkflowNameWhenTemplateOptsIn(): void
    {
        $example = $this->createExampleWithStrictTemplate();
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

        $this->assertSame(
            'strict',
            $request->getWorkflowName(),
            \sprintf(
                'Template-tag-driven workflow resolution failed: request was created with workflowName="%s"; expected "strict".',
                $request->getWorkflowName(),
            ),
        );
    }

    public function testStrictWorkflowRequiresTwoApprovalsBeforePublish(): void
    {
        $example = $this->createExampleWithStrictTemplate();
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

        // First approver: test user (different from request creator).
        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=approve', $request->getId()),
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $afterFirstApproval = $this->workflowTransitionRequestRepository->getOneBy(['id' => $request->getId()]);
        $this->assertSame(
            WorkflowTransitionRequestStatusEnum::PENDING,
            $this->resolveRequestStatus($afterFirstApproval),
            'After one approval the request must remain PENDING because strict workflow requires count=2.',
        );

        // Second approver, distinct from both the request creator and the first approver.
        $secondApprover = $this->createRequestCreator('second_approver');
        $request->addApproval($secondApprover);
        static::getEntityManager()->flush();

        $this->contentManager->applyTransition($example, $dimensionAttributes, WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH);
        static::getEntityManager()->flush();

        $finalRequest = $this->workflowTransitionRequestRepository->getOneBy(['id' => $request->getId()]);
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PUBLISHED, $this->resolveRequestStatus($finalRequest));
    }

    private function createExampleWithStrictTemplate(): Example
    {
        $example = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => 'example-strict-workflow',
                        'title' => 'Published Title',
                        'url' => '/published-title-strict',
                    ],
                    'draft' => [
                        'template' => 'example-strict-workflow',
                        'title' => 'Draft Title',
                        'url' => '/draft-title-strict',
                    ],
                ],
            ],
            ['create_route' => true],
        );
        static::getEntityManager()->flush();

        return $example;
    }
}
