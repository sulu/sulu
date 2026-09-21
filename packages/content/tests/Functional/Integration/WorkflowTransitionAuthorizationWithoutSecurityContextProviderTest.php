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
use Sulu\Content\Tests\Application\WithoutSecurityContextProviderKernel;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * A resource key without a security context provider publishes as it did before the review flow,
 * until a request workflow covers its content.
 */
#[CoversNothing]
class WorkflowTransitionAuthorizationWithoutSecurityContextProviderTest extends SuluTestCase
{
    use WorkflowTransitionRequestTrait;

    private const NO_WORKFLOW_TEMPLATE = 'default';

    private KernelBrowser $client;

    protected static function getKernelClass(): string
    {
        return WithoutSecurityContextProviderKernel::class;
    }

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );

        self::purgeDatabase();

        $this->grantTestUserViewAndEditOnly();
        $this->renameTestUserTo('editor_without_live');
        $this->client->setServerParameter('PHP_AUTH_USER', 'editor_without_live');
    }

    public function testPublishOutsideARequestWorkflowNeedsNoProvider(): void
    {
        $example = $this->createExampleAtDraft(self::NO_WORKFLOW_TEMPLATE);

        $this->client->request(
            'POST',
            \sprintf('/admin/api/examples/%d?action=publish&locale=en', $example->getId()),
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{workflowPlace: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('published', $content['workflowPlace']);
    }

    /**
     * Content a request workflow covers is always authorized, so the missing provider is reported
     * instead of the edit permission publishing past the review.
     */
    public function testPublishInARequestWorkflowReportsTheMissingProvider(): void
    {
        $example = $this->createExampleAtDraft(self::REVIEW_TEMPLATE);

        $this->client->request(
            'POST',
            \sprintf('/admin/api/examples/%d?action=publish&locale=en', $example->getId()),
        );

        $this->assertHttpStatusCode(500, $this->client->getResponse());

        /** @var array{message: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'No security context provider is registered for resource key "examples"',
            $content['message'],
        );
    }
}
