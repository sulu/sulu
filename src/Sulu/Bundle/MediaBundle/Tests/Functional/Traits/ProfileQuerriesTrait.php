<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Traits;

trait ProfileQuerriesTrait
{
    private function requestPageAndGetQueries(string $method, string $url): array
    {
        self::ensureKernelShutdown();
        $this->client = static::createAuthenticatedClient();
        $this->client->enableProfiler();
        $this->client->request($method, $url);
        $response = $this->client->getResponse();

        $profiler = $this->client->getProfile();
        $this->assertNotFalse($profiler, 'Profiler must be enabled');
        $this->assertNotNull($profiler);

        $dbCollector = $profiler->getCollector('db');
        $this->assertInstanceOf(DoctrineDataCollector::class, $dbCollector);

        $queriesData = $dbCollector->getQueries();
        $this->assertArrayHasKey('default', $queriesData);

        /** @var list<array{sql: string}> $queries */
        $queries = $queriesData['default'];

        return [
            'queries' => $queries,
            'response' => $response,
        ];
    }
}
