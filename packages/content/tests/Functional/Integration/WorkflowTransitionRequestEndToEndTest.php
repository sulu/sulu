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
use Sulu\Content\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[CoversNothing]
class WorkflowTransitionRequestEndToEndTest extends SuluTestCase
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

    private function reloadExample(int $id): Example
    {
        /** @var ExampleRepository $repository */
        $repository = static::getContainer()->get('example_test.example_repository');

        return $repository->getOneBy(['id' => $id], ['example_admin' => true]);
    }

    public function testApproveDeniedForUserWithoutReviewPermission(): void
    {
        $this->grantTestUserViewAndEditOnly();

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

        $this->renameTestUserTo('reviewer_no_review');

        $this->client->setServerParameter('PHP_AUTH_USER', 'reviewer_no_review');
        $this->client->setServerParameter('PHP_AUTH_PW', 'test');

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=approve', $request->getId()),
        );

        $response = $this->client->getResponse();
        $this->assertSame(
            403,
            $response->getStatusCode(),
            \sprintf('Expected 403 but got %d. Body: %s', $response->getStatusCode(), (string) $response->getContent()),
        );
    }

    /**
     * The requests of a content, and the comments on them, are only readable by someone who may read
     * the content itself. Without this the endpoints would answer to anyone signed in, whatever they
     * are allowed to see.
     */
    public function testReadingIsDeniedForUserWithoutViewPermission(): void
    {
        $this->grantTestUserNoPermissions();

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

        $this->renameTestUserTo('reader_no_view');
        $this->client->setServerParameter('PHP_AUTH_USER', 'reader_no_view');
        $this->client->setServerParameter('PHP_AUTH_PW', 'test');

        $this->client->request(
            'GET',
            \sprintf(
                '/admin/api/workflow-transition-requests.json?%s',
                \http_build_query([
                    'resourceKey' => Example::RESOURCE_KEY,
                    'resourceId' => (string) $example->getId(),
                    'locale' => 'en',
                ]),
            ),
        );
        $this->assertSame(
            403,
            $this->client->getResponse()->getStatusCode(),
            'Listing the requests of a content takes the view permission on that content.',
        );

        $this->client->request(
            'GET',
            \sprintf('/admin/api/workflow-transition-requests/%s.json', $request->getId()),
        );
        $this->assertSame(
            403,
            $this->client->getResponse()->getStatusCode(),
            'Reading a single request takes the view permission on the content behind it.',
        );
    }

    /**
     * A typo in the action name is the caller's mistake, not a permission problem. Without resolving
     * the action first, an unrecognised one is checked against `review` and anybody who only holds
     * `edit` is told 403, which points them at the wrong thing entirely.
     */
    public function testUnknownActionAnswers400EvenWithoutTheReviewPermission(): void
    {
        $this->grantTestUserViewAndEditOnly();

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

        $this->renameTestUserTo('editor_no_review');
        $this->client->setServerParameter('PHP_AUTH_USER', 'editor_no_review');
        $this->client->setServerParameter('PHP_AUTH_PW', 'test');

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=explode', $request->getId()),
        );

        $this->assertSame(
            400,
            $this->client->getResponse()->getStatusCode(),
            'An unrecognised action is a bad request, whatever the caller may or may not do.',
        );
    }

    public function testHappyPathFromDraftThroughApprovalToPublish(): void
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
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PENDING, $this->resolveRequestStatus($request));

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=approve', $request->getId()),
            ['comment' => 'Looks great'],
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array<string, mixed> $approveContent */
        $approveContent = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('approved', $approveContent['status']);

        $reloadedExample = $this->reloadExample((int) $example->getId());
        $this->contentManager->applyTransition(
            $reloadedExample,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
        );
        static::getEntityManager()->flush();

        $finalRequest = $this->workflowTransitionRequestRepository->getOneBy(['id' => $request->getId()]);
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PUBLISHED, $this->resolveRequestStatus($finalRequest));
        $this->assertNull($finalRequest->getActiveKey());

        $liveContent = $this->contentManager->resolve(
            $reloadedExample,
            ['stage' => DimensionContentInterface::STAGE_LIVE, 'locale' => 'en'],
        );
        $this->assertSame('Draft Title', $liveContent->getTemplateData()['title'] ?? null);
    }

    public function testRejectionKeepsTheRequestOpenUntilTheReviewerApproves(): void
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
        $url = \sprintf('/admin/api/workflow-transition-requests/%s.json', $request->getId());

        $this->client->request('POST', $url . '?action=reject', ['comment' => 'Needs changes']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array<string, mixed> $rejectContent */
        $rejectContent = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('pending', $rejectContent['status']);

        $rejectedRequest = $this->workflowTransitionRequestRepository->getOneBy(['id' => $request->getId()]);
        $this->assertNotNull($rejectedRequest->getActiveKey(), 'A rejected request stays open for the reviewer to change their mind.');

        $this->client->request('POST', $url . '?action=approve', ['comment' => 'Resolved']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array<string, mixed> $approveContent */
        $approveContent = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('approved', $approveContent['status']);

        $approvedRequest = $this->workflowTransitionRequestRepository->getOneBy(['id' => $request->getId()]);
        $this->assertSame(WorkflowTransitionRequestStatusEnum::APPROVED, $this->resolveRequestStatus($approvedRequest));
    }

    public function testCreatorCannotApproveOwnRequestViaHttp(): void
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

        // Simulate a creator that matches the authenticated http user: outside an HTTP request the BlameSubscriber
        // does not fire, so we set the creator explicitly to reproduce the production scenario.
        $request->setCreator(static::getTestUser());
        static::getEntityManager()->flush();

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=approve', $request->getId()),
        );

        $response = $this->client->getResponse();
        $this->assertSame(403, $response->getStatusCode());

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame(
            'You cannot review a workflow transition request you created yourself.',
            $content['detail'] ?? null,
        );
    }
}
