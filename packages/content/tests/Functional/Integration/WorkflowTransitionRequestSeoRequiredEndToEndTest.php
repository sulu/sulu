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
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestPreValidationFailedException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The pre-validator gate: content missing SEO or excerpt cannot enter review. The transition aborts,
 * no request row is created, and the admin gets a 422 naming every missing field.
 */
#[CoversNothing]
class WorkflowTransitionRequestSeoRequiredEndToEndTest extends SuluTestCase
{
    use WorkflowTransitionRequestTrait;

    private const SEO_TEMPLATE = 'example-seo-workflow';

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

        // The transition subscriber reads token storage to attribute the request creator. Authenticating
        // as a *different* user than the http test user prevents the self-review guard from firing.
        $this->authenticateAsRequestCreator();
    }

    public function testRequestForReviewBlockedWhenSeoMissing(): void
    {
        $example = $this->createExampleWithoutSeo();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        try {
            $this->contentManager->applyTransition(
                $example,
                $dimensionAttributes,
                WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
            );
            $this->fail('Expected the pre-validators to abort the transition.');
        } catch (WorkflowTransitionRequestPreValidationFailedException $exception) {
            $this->assertSame(
                [
                    'sulu_content.workflow_transition_request.seo_required.missing',
                    'sulu_content.workflow_transition_request.excerpt_required.missing',
                ],
                \array_column(
                    \array_merge(...\array_column($this->preValidationResults($exception), 'messages')),
                    'key',
                ),
                'The exception carries keys, the serializer renders them for the request locale.',
            );
        }

        $this->assertNull(
            $this->workflowTransitionRequestRepository->findOneBy([
                'resourceKey' => Example::RESOURCE_KEY,
                'resourceId' => (string) $example->getId(),
                'locale' => 'en',
            ]),
            'A failing pre-validator must prevent the request from being created at all.',
        );
    }

    public function testRequestForReviewReturns422WhenSeoMissing(): void
    {
        $example = $this->createExampleWithoutSeo();

        $this->client->request(
            'POST',
            \sprintf(
                '/admin/api/examples/%d?action=%s&locale=en',
                $example->getId(),
                WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
            ),
        );

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(422, $response);

        /** @var array{code?: int, detail?: string, preValidationResults?: list<array{key: string, passed: bool, messages: list<array{key: string|null, parameters: array<string, float|int|string>, text: string|null}>}>} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $this->assertSame(
            [
                'sulu_content.workflow_transition_request.seo_required.missing',
                'sulu_content.workflow_transition_request.excerpt_required.missing',
            ],
            $this->failureMessages($content['preValidationResults'] ?? []),
            'The 422 carries one entry per failed check, keyed so the admin renders them itself.',
        );
        $this->assertSame(
            'SEO fields are still missing: title, description. Excerpt fields are still missing: title.',
            $content['detail'] ?? null,
            'A client that reads only `detail` still gets every open point, translated.',
        );
        $this->assertSame(
            WorkflowTransitionRequestPreValidationFailedException::EXCEPTION_CODE_PRE_VALIDATION_FAILED,
            $content['code'] ?? null,
            'The admin recognises the failure by its code.',
        );
    }

    /**
     * Pre-validators are the workflow's hard requirements, so publishing without asking for a review
     * has to meet them too.
     */
    public function testPublishWithoutRequestReturns422WhenSeoMissing(): void
    {
        $example = $this->createExampleWithoutSeo();

        $this->client->request(
            'POST',
            \sprintf(
                '/admin/api/examples/%d?action=%s&locale=en',
                $example->getId(),
                WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            ),
        );

        $this->assertHttpStatusCode(422, $this->client->getResponse());

        /** @var array{preValidationResults?: list<array{key: string, passed: bool, messages: list<array{key: string|null, parameters: array<string, float|int|string>, text: string|null}>}>} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame(
            [
                'sulu_content.workflow_transition_request.seo_required.missing',
                'sulu_content.workflow_transition_request.excerpt_required.missing',
            ],
            $this->failureMessages($content['preValidationResults'] ?? []),
        );
    }

    public function testPublishSucceedsWhenSeoFilledAtPublishTime(): void
    {
        $example = $this->createExampleWithSeoAndExcerptTitle();
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

        $this->client->request(
            'POST',
            \sprintf('/admin/api/workflow-transition-requests/%s.json?action=approve', $request->getId()),
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
        );
        static::getEntityManager()->flush();

        $finalRequest = $this->workflowTransitionRequestRepository->getOneBy(['id' => $request->getId()]);
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PUBLISHED, $this->resolveRequestStatus($finalRequest));
    }

    private function createExampleWithoutSeo(): Example
    {
        $example = static::createExample(
            [
                'en' => [
                    'draft' => [
                        'template' => self::SEO_TEMPLATE,
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

    private function createExampleWithSeoAndExcerptTitle(): Example
    {
        $example = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => self::SEO_TEMPLATE,
                        'title' => 'Published Title',
                        'url' => '/published-title-seo',
                    ],
                    'draft' => [
                        'template' => self::SEO_TEMPLATE,
                        'title' => 'Draft Title',
                        'url' => '/draft-title-seo',
                        'seo' => ['title' => 'SEO Title', 'description' => 'SEO Description'],
                        'excerpt' => ['title' => 'Excerpt Title'],
                    ],
                ],
            ],
            ['create_route' => true],
        );
        static::getEntityManager()->flush();

        return $example;
    }

    /**
     * @return list<array{key: string, passed: bool, messages: list<array{key: string|null, parameters: array<string, float|int|string>, text: string|null}>}>
     */
    private function preValidationResults(WorkflowTransitionRequestPreValidationFailedException $exception): array
    {
        /** @var list<array{key: string, passed: bool, messages: list<array{key: string|null, parameters: array<string, float|int|string>, text: string|null}>}> $results */
        $results = $exception->getResponseData()['preValidationResults'];

        return $results;
    }

    /**
     * Flattens the 422 payload to the messages of the checks that actually failed, so a test can
     * assert on what the author is told without restating the passing checks.
     *
     * @param list<array{key: string, passed: bool, messages: list<array{key: string|null, parameters: array<string, float|int|string>, text: string|null}>}> $results
     *
     * @return list<string|null>
     */
    private function failureMessages(array $results): array
    {
        $messages = [];

        foreach ($results as $result) {
            if ($result['passed']) {
                continue;
            }

            foreach ($result['messages'] as $message) {
                $messages[] = $message['key'];
            }
        }

        return $messages;
    }
}
