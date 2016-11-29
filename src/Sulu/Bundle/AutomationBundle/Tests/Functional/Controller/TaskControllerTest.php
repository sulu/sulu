<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Sulu\Bundle\AutomationBundle\Tests\Handler\TestHandler;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * Tests for task-api.
 */
class TaskControllerTest extends SuluTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->purgeDatabase();
    }

    public function testCGet()
    {
        $postData = [
            $this->testPost(),
            $this->testPost(),
            $this->testPost(),
        ];

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks?fields=id,schedule,handlerClass,taskName');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(3, $responseData['total']);
        $this->assertCount(3, $responseData['_embedded']['tasks']);

        $embedded = $responseData['_embedded']['tasks'];
        for ($i = 0, $length = count($postData); $i < $length; ++$i) {
            $this->assertContains(
                [
                    'id' => $postData[$i]['id'],
                    'handlerClass' => $postData[$i]['handlerClass'],
                    'schedule' => $postData[$i]['schedule'],
                    'taskName' => $postData[$i]['taskName'],
                    'status' => $postData[$i]['status'],
                ],
                $embedded
            );
        }
    }

    public function testCGetWithIds()
    {
        $postData = [
            $this->testPost(),
            $this->testPost(),
            $this->testPost(),
        ];

        $ids = [$postData[2]['id'], $postData[0]['id']];

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks?ids=' . implode(',', $ids));
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $responseData['total']);
        $this->assertCount(2, $responseData['_embedded']['tasks']);

        $embedded = $responseData['_embedded']['tasks'];
        for ($i = 0, $length = count($ids); $i < $length; ++$i) {
            $this->assertEquals($ids[$i], $embedded[$i]['id']);
        }
    }

    public function testCGetWithLocales()
    {
        $postData = [
            $this->testPost(TestHandler::class, '+1 day', 'ThisClass', 1, 'de'),
            $this->testPost(TestHandler::class, '+1 day', 'ThisClass', 1, 'en'),
            $this->testPost(TestHandler::class, '+1 day', 'ThisClass', 1, 'de'),
        ];


        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks?locale=de&fields=id,schedule,handlerClass,taskName');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $responseData['total']);
        $this->assertCount(2, $responseData['_embedded']['tasks']);

        $items = [$postData[0], $postData[2]];
        $embedded = $responseData['_embedded']['tasks'];
        for ($i = 0, $length = count($items); $i < $length; ++$i) {
            $this->assertContains(
                [
                    'id' => $items[$i]['id'],
                    'schedule' => $items[$i]['schedule'],
                    'handlerClass' => $items[$i]['handlerClass'],
                    'taskName' => $items[$i]['taskName'],
                    'status' => $postData[$i]['status'],
                ],
                $embedded
            );
        }
    }

    public function testCGetWithEntity()
    {
        $postData = [
            $this->testPost(TestHandler::class, '+1 day', 'ThisClass', 1),
            $this->testPost(TestHandler::class, '+1 day', 'ThisClass', 2),
            $this->testPost(TestHandler::class, '+1 day', 'OtherClass', 1),
        ];

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks?entity-class=ThisClass&entity-id=1');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1, $responseData['total']);
        $this->assertCount(1, $responseData['_embedded']['tasks']);

        $embedded = $responseData['_embedded']['tasks'];
        $this->assertEquals($postData[0]['id'], $embedded[0]['id']);
    }

    public function testCGetWithFutureSchedule()
    {
        $postData = [
            $this->testPost(TestHandler::class, '+1 day', 'ThisClass', 1),
            $this->testPost(TestHandler::class, '-1 day', 'ThisClass', 2),
            $this->testPost(TestHandler::class, '+1 day', 'OtherClass', 1),
        ];

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks?fields=id,schedule,handlerClass,taskName&schedule=future');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $responseData['total']);
        $this->assertCount(2, $responseData['_embedded']['tasks']);

        $items = [$postData[0], $postData[2]];
        $embedded = $responseData['_embedded']['tasks'];
        for ($i = 0, $length = count($items); $i < $length; ++$i) {
            $this->assertContains(
                [
                    'id' => $items[$i]['id'],
                    'schedule' => $items[$i]['schedule'],
                    'handlerClass' => $items[$i]['handlerClass'],
                    'taskName' => $items[$i]['taskName'],
                    'status' => $postData[$i]['status'],
                ],
                $embedded
            );
        }
    }

    public function testCGetWithPastSchedule()
    {
        $postData = [
            $this->testPost(TestHandler::class, '-1 day', 'ThisClass', 1),
            $this->testPost(TestHandler::class, '+1 day', 'ThisClass', 2),
            $this->testPost(TestHandler::class, '-1 day', 'OtherClass', 1),
        ];

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks?fields=id,schedule,handlerClass,taskName&schedule=past');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $responseData['total']);
        $this->assertCount(2, $responseData['_embedded']['tasks']);

        $items = [$postData[0], $postData[2]];
        $embedded = $responseData['_embedded']['tasks'];
        for ($i = 0, $length = count($items); $i < $length; ++$i) {
            $this->assertContains(
                [
                    'id' => $items[$i]['id'],
                    'schedule' => $items[$i]['schedule'],
                    'handlerClass' => $items[$i]['handlerClass'],
                    'taskName' => $items[$i]['taskName'],
                    'status' => $postData[$i]['status'],
                ],
                $embedded
            );
        }
    }

    public function testPost(
        $handlerClass = TestHandler::class,
        $schedule = '+1 day',
        $entityClass = 'ThisClass',
        $entityId = 1,
        $locale = 'de'
    ) {
        $date = new \DateTime($schedule);
        $scheduleDate = $date->format('Y-m-d\TH:i:sO');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/tasks',
            [
                'handlerClass' => $handlerClass,
                'schedule' => $scheduleDate,
                'entityClass' => $entityClass,
                'entityId' => $entityId,
                'locale' => $locale,
            ]
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($handlerClass, $responseData['handlerClass']);
        $this->assertEquals($scheduleDate, $responseData['schedule']);
        $this->assertEquals($locale, $responseData['locale']);

        return $responseData;
    }

    public function testPut($handlerClass = TestHandler::class, $schedule = '+2 day')
    {
        $postData = $this->testPost();

        $date = new \DateTime($schedule);
        $scheduleDate = $date->format('Y-m-d\TH:i:sO');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/tasks/' . $postData['id'],
            ['handlerClass' => $handlerClass, 'schedule' => $scheduleDate]
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($postData['id'], $responseData['id']);
        $this->assertEquals($handlerClass, $responseData['handlerClass']);
        $this->assertEquals($scheduleDate, $responseData['schedule']);
        $this->assertEquals(TestHandler::TITLE, $responseData['taskName']);
    }

    public function testGet()
    {
        $postData = $this->testPost();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks/' . $postData['id']);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($postData['id'], $responseData['id']);
        $this->assertEquals($postData['handlerClass'], $responseData['handlerClass']);
        $this->assertEquals($postData['schedule'], $responseData['schedule']);
        $this->assertEquals($postData['locale'], $responseData['locale']);
    }

    public function testDelete()
    {
        $postData = $this->testPost();

        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/tasks/' . $postData['id']);
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request('GET', '/api/tasks/' . $postData['id']);
        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testCDelete()
    {
        $postData = [
            $this->testPost(),
            $this->testPost(),
            $this->testPost(),
        ];

        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/tasks?ids=' . $postData[0]['id'] . ',' . $postData[1]['id']);
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request('GET', '/api/tasks/' . $postData[0]['id']);
        $this->assertHttpStatusCode(404, $client->getResponse());
        $client->request('GET', '/api/tasks/' . $postData[1]['id']);
        $this->assertHttpStatusCode(404, $client->getResponse());
        $client->request('GET', '/api/tasks/' . $postData[2]['id']);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($postData[2]['id'], $responseData['id']);
    }
}
