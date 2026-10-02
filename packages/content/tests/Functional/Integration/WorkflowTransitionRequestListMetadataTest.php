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

/**
 * The insights list reads its columns from this metadata, so it has to expose what the list endpoint returns.
 */
#[CoversNothing]
class WorkflowTransitionRequestListMetadataTest extends SuluTestCase
{
    public function testTheListMetadataExposesTheColumnsOfTheListEndpoint(): void
    {
        $client = $this->createAuthenticatedClient([], ['HTTP_ACCEPT' => 'application/json']);

        $client->jsonRequest('GET', '/admin/metadata/list/workflow_transition_requests');

        $this->assertHttpStatusCode(200, $client->getResponse());

        /** @var array<string, mixed> $metadata */
        $metadata = \json_decode((string) $client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $metadata);
        $this->assertArrayHasKey('requester', $metadata);
        $this->assertArrayHasKey('status', $metadata);
    }
}
