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
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[CoversNothing]
class WorkflowTransitionRequestControllerTest extends SuluTestCase
{
    use WorkflowTransitionRequestTrait;

    private KernelBrowser $client;

    private WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );

        self::purgeDatabase();

        $this->workflowTransitionRequestRepository = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class);
    }

    public function testCgetReturnsAllRequestsOfTheContentNewestFirst(): void
    {
        $creator = $this->createRequestCreator();

        // Only one request per content may be open, so each one is closed before the next is added.
        $cancelled = $this->persistWorkflowTransitionRequest('examples', '1', 'en', $creator);
        $cancelled->cancel();
        static::getEntityManager()->flush();
        $open = $this->persistWorkflowTransitionRequest('examples', '1', 'en', $creator);

        $this->client->request('GET', '/admin/api/workflow-transition-requests.json?resourceKey=examples&resourceId=1&locale=en');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{_embedded: array{workflow_transition_requests: array<int, array<string, mixed>>}, total: int, page: int, limit: int, pages: int} $content */
        $content = \json_decode((string) $response->getContent(), true);

        $rows = $content['_embedded']['workflow_transition_requests'];
        $this->assertSame([$open->getId(), $cancelled->getId()], \array_column($rows, 'id'));
        $this->assertSame(['pending', 'cancelled'], \array_column($rows, 'status'));
        $this->assertSame(['Request Creator', 'Request Creator'], \array_column($rows, 'requester'));
        $this->assertSame(2, $content['total']);
        $this->assertSame(1, $content['page']);
        $this->assertSame(1, $content['pages']);
    }

    public function testCgetPaginates(): void
    {
        $first = $this->persistWorkflowTransitionRequest('examples', '1', 'en');
        $first->cancel();
        static::getEntityManager()->flush();
        $second = $this->persistWorkflowTransitionRequest('examples', '1', 'en');

        $this->client->request('GET', '/admin/api/workflow-transition-requests.json?resourceKey=examples&resourceId=1&locale=en&limit=1&page=2');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{_embedded: array{workflow_transition_requests: array<int, array<string, mixed>>}, total: int, pages: int} $content */
        $content = \json_decode((string) $response->getContent(), true);

        $this->assertSame([$first->getId()], \array_column($content['_embedded']['workflow_transition_requests'], 'id'));
        $this->assertSame(2, $content['total']);
        $this->assertSame(2, $content['pages']);

        $this->client->request('GET', '/admin/api/workflow-transition-requests.json?resourceKey=examples&resourceId=1&locale=en&limit=1&page=1');

        /** @var array{_embedded: array{workflow_transition_requests: array<int, array<string, mixed>>}} $firstPage */
        $firstPage = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame([$second->getId()], \array_column($firstPage['_embedded']['workflow_transition_requests'], 'id'));
    }

    public function testCgetIgnoresRequestsOfAnotherLocale(): void
    {
        $english = $this->persistWorkflowTransitionRequest('examples', '1', 'en');
        $this->persistWorkflowTransitionRequest('examples', '1', 'de');

        $this->client->request('GET', '/admin/api/workflow-transition-requests.json?resourceKey=examples&resourceId=1&locale=en');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{_embedded: array{workflow_transition_requests: array<int, array<string, mixed>>}, total: int} $content */
        $content = \json_decode((string) $response->getContent(), true);

        $this->assertSame([$english->getId()], \array_column($content['_embedded']['workflow_transition_requests'], 'id'));
        $this->assertSame(1, $content['total']);
    }

    public function testCgetWithoutResourceIdReturns400(): void
    {
        $this->client->request('GET', '/admin/api/workflow-transition-requests.json?resourceKey=examples&locale=en');

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testCgetWithUnknownResourceKeyReturns400(): void
    {
        $this->client->request('GET', '/admin/api/workflow-transition-requests.json?resourceKey=unicorns&resourceId=1&locale=en');

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testGetReturnsRequest(): void
    {
        $workflowTransitionRequest = $this->persistWorkflowTransitionRequest('examples', '1', 'en');

        $this->client->request('GET', \sprintf('/admin/api/workflow-transition-requests/%s.json', $workflowTransitionRequest->getId()));

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);

        $this->assertSame($workflowTransitionRequest->getId(), $content['id']);
        $this->assertSame('examples', $content['resourceKey']);
        $this->assertSame('1', $content['resourceId']);
        $this->assertSame('en', $content['locale']);
        $this->assertSame('pending', $content['status']);
        $this->assertArrayHasKey('requestedAt', $content);
        $this->assertSame([], $content['approvals']);
        $this->assertSame([], $content['checks']);
    }

    public function testGetNotFoundReturns404(): void
    {
        $this->client->request('GET', '/admin/api/workflow-transition-requests/00000000-0000-0000-0000-000000000000.json');

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testApproveCreatesReviewerAndTransitionsStatus(): void
    {
        $workflowTransitionRequest = $this->persistWorkflowTransitionRequest('examples', '1', 'en', $this->createRequestCreator());

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=approve', $workflowTransitionRequest->getId()),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame('approved', $content['status']);
        /** @var array<int, array<string, mixed>> $approvals */
        $approvals = $content['approvals'];
        $this->assertCount(1, $approvals);
        $this->assertSame('approved', $approvals[0]['status']);
        $this->assertSame([], $approvals[0]['messages'], 'An approval without a message says nothing.');
    }

    public function testApproveWithCommentPersistsComment(): void
    {
        $workflowTransitionRequest = $this->persistWorkflowTransitionRequest('examples', '1', 'en', $this->createRequestCreator());

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=approve', $workflowTransitionRequest->getId()),
            ['comment' => 'Looks good to me'],
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        /** @var array<int, array<string, mixed>> $approvals */
        $approvals = $content['approvals'];
        $this->assertSame([['key' => null, 'parameters' => [], 'text' => 'Looks good to me']], $approvals[0]['messages']);
    }

    public function testRejectRecordsTheRowWithoutApprovingTheRequest(): void
    {
        $workflowTransitionRequest = $this->persistWorkflowTransitionRequest('examples', '1', 'en', $this->createRequestCreator());

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=reject', $workflowTransitionRequest->getId()),
            ['comment' => 'Not yet'],
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame('pending', $content['status']);
        $this->assertSame(['required' => 1, 'approved' => 0, 'rejected' => 1], $content['approvalProgress']);
        /** @var array<int, array<string, mixed>> $approvals */
        $approvals = $content['approvals'];
        $this->assertCount(1, $approvals);
        $this->assertSame('rejected', $approvals[0]['status']);
        $this->assertSame([['key' => null, 'parameters' => [], 'text' => 'Not yet']], $approvals[0]['messages']);
        $this->assertNotNull($approvals[0]['decidedAt']);
    }

    public function testRejectWithoutCommentReturns400(): void
    {
        $workflowTransitionRequest = $this->persistWorkflowTransitionRequest('examples', '1', 'en', $this->createRequestCreator());

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=reject', $workflowTransitionRequest->getId()),
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testRejectWithWhitespaceOnlyCommentReturns400(): void
    {
        $workflowTransitionRequest = $this->persistWorkflowTransitionRequest('examples', '1', 'en', $this->createRequestCreator());

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=reject', $workflowTransitionRequest->getId()),
            ['comment' => "  \n\t "],
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testApproveTwiceBySameUserUpdatesReviewer(): void
    {
        $workflowTransitionRequest = $this->persistWorkflowTransitionRequest('examples', '1', 'en', $this->createRequestCreator());
        $url = \sprintf('/admin/api/workflow-transition-requests/%s.json?action=approve', $workflowTransitionRequest->getId());

        $this->client->request('POST', $url, ['comment' => 'First']);
        $this->client->request('POST', $url, ['comment' => 'Second']);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        /** @var array<int, array<string, mixed>> $approvals */
        $approvals = $content['approvals'];
        $this->assertCount(1, $approvals);
        $this->assertSame([['key' => null, 'parameters' => [], 'text' => 'Second']], $approvals[0]['messages']);
    }

    public function testRejectAfterApproveWithdrawsTheApproval(): void
    {
        $workflowTransitionRequest = $this->persistWorkflowTransitionRequest('examples', '1', 'en', $this->createRequestCreator());
        $baseUrl = \sprintf('/admin/api/workflow-transition-requests/%s.json', $workflowTransitionRequest->getId());

        $this->client->request('POST', $baseUrl . '?action=approve');
        $this->client->request('POST', $baseUrl . '?action=reject', ['comment' => 'On second thought']);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame('pending', $content['status']);
        /** @var array<int, array<string, mixed>> $approvals */
        $approvals = $content['approvals'];
        $this->assertCount(1, $approvals);
        $this->assertSame('rejected', $approvals[0]['status']);
    }

    public function testCancelActionIsNotExposed(): void
    {
        $workflowTransitionRequest = $this->persistWorkflowTransitionRequest('examples', '1', 'en', static::getTestUser());

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=cancel', $workflowTransitionRequest->getId()),
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testApproveOnCancelledReturnsTranslatable409(): void
    {
        $workflowTransitionRequest = $this->persistWorkflowTransitionRequest('examples', '1', 'en', static::getTestUser());
        $workflowTransitionRequest->cancel();
        static::getEntityManager()->flush();

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=approve', $workflowTransitionRequest->getId()),
        );

        $response = $this->client->getResponse();
        $this->assertSame(409, $response->getStatusCode());

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame(
            'This workflow transition request has already been closed and cannot be modified.',
            $content['detail'] ?? null,
        );
    }

    public function testUnknownActionReturns400(): void
    {
        $workflowTransitionRequest = $this->persistWorkflowTransitionRequest('examples', '1', 'en');

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=explode', $workflowTransitionRequest->getId()),
        );

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testActionOnMissingIdReturns404(): void
    {
        $this->client->request(
            'POST',
            '/admin/api/workflow-transition-requests/00000000-0000-0000-0000-000000000000.json?action=approve',
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    private function persistWorkflowTransitionRequest(string $resourceKey, string $resourceId, string $locale, ?User $creator = null): WorkflowTransitionRequest
    {
        $workflowTransitionRequest = new WorkflowTransitionRequest($resourceKey, $resourceId, $locale, 'review');
        if (null !== $creator) {
            $workflowTransitionRequest->setCreator($creator);
        }

        $this->workflowTransitionRequestRepository->add($workflowTransitionRequest);
        static::getEntityManager()->flush();

        $this->assertSame(WorkflowTransitionRequestStatusEnum::PENDING, $this->resolveRequestStatus($workflowTransitionRequest));

        return $workflowTransitionRequest;
    }
}
