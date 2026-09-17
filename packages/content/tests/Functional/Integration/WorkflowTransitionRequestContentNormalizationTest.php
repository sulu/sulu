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
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Functional\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;
use Sulu\Content\Tests\Functional\Traits\CreateTagTrait;
use Sulu\Content\Tests\Traits\CreateExampleTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[CoversNothing]
class WorkflowTransitionRequestContentNormalizationTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateExampleTrait;
    use CreateMediaTrait;
    use CreateTagTrait;

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

    public function testGetReturnsActiveWorkflowTransitionRequestWhenPresent(): void
    {
        $example = $this->createExampleAtDraft();
        $workflowTransitionRequest = new WorkflowTransitionRequest(Example::RESOURCE_KEY, (string) $example->getId(), 'en', 'review');
        $this->workflowTransitionRequestRepository->add($workflowTransitionRequest);
        static::getEntityManager()->flush();

        $this->client->request('GET', \sprintf('/admin/api/examples/%d?locale=en', $example->getId()));
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('activeWorkflowTransitionRequest', $content);
        $this->assertNotNull($content['activeWorkflowTransitionRequest']);
        /** @var array<string, mixed> $activeRequest */
        $activeRequest = $content['activeWorkflowTransitionRequest'];
        $this->assertSame($workflowTransitionRequest->getId(), $activeRequest['id']);
        $this->assertSame('pending', $activeRequest['status']);
    }

    /**
     * A workflow can be renamed or removed while requests are open. The row still has to render, or
     * one leftover request takes down every list that shows it.
     */
    public function testRequestNamingAnUnconfiguredWorkflowIsReportedUnknown(): void
    {
        $example = $this->createExampleAtDraft();
        $workflowTransitionRequest = new WorkflowTransitionRequest(Example::RESOURCE_KEY, (string) $example->getId(), 'en', 'gone');
        $this->workflowTransitionRequestRepository->add($workflowTransitionRequest);
        static::getEntityManager()->flush();

        $this->client->request('GET', \sprintf('/admin/api/examples/%d?locale=en', $example->getId()));
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        /** @var array<string, mixed> $activeRequest */
        $activeRequest = $content['activeWorkflowTransitionRequest'];
        $this->assertSame('unknown', $activeRequest['status']);
        $this->assertSame(
            ['required' => 0, 'approved' => 0, 'rejected' => 0],
            $activeRequest['approvalProgress'],
            'Nothing can be required by a workflow that is not there.',
        );
    }

    public function testGetReturnsNullActiveWorkflowTransitionRequestWhenAbsent(): void
    {
        $example = $this->createExampleAtDraft();

        $this->client->request('GET', \sprintf('/admin/api/examples/%d?locale=en', $example->getId()));
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('activeWorkflowTransitionRequest', $content);
        $this->assertNull($content['activeWorkflowTransitionRequest']);
    }

    public function testGetOmitsTheReviewFieldsForContentWithoutAWorkflow(): void
    {
        $example = $this->createExampleAtDraft('example-2');

        $this->client->request('GET', \sprintf('/admin/api/examples/%d?locale=en', $example->getId()));
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('activeWorkflowTransitionRequest', $content);
        $this->assertArrayNotHasKey('workflowTransitionRequestEnabled', $content);
        $this->assertArrayNotHasKey('_locked', $content);
    }

    private function createExampleAtDraft(string $template = 'example-review-workflow'): Example
    {
        $example = static::createExample(
            [
                'en' => [
                    'draft' => [
                        'template' => $template,
                        'title' => 'Draft Title',
                        'url' => '/draft-title',
                    ],
                ],
            ],
            ['create_route' => true],
        );
        static::getEntityManager()->flush();

        return $example;
    }
}
