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

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * Tests for task-api.
 */
class TaskControllerTest extends SuluTestCase
{
    public function setUp()
    {
        $this->purgeDatabase();
    }

    public function testCGet()
    {
        $postData = [
            $this->testPost('sulu_content.publish'),
            $this->testPost('sulu_content.publish'),
            $this->testPost('sulu_content.publish'),
        ];

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(3, $responseData['total']);
        $this->assertCount(3, $responseData['_embedded']['tasks']);

        $embedded = $responseData['_embedded']['tasks'];

        for ($i = 0, $length = count($postData); $i < $length; ++$i) {
            $this->assertContains(
                [
                    'id' => $postData[$i]['id'],
                    'taskName' => $postData[$i]['taskName'],
                    'schedule' => $postData[$i]['schedule'],
                ],
                $embedded
            );
        }
    }

    public function testCGetWithIds()
    {
        $postData = [
            $this->testPost('sulu_content.publish'),
            $this->testPost('sulu_content.publish'),
            $this->testPost('sulu_content.publish'),
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
            $this->testPost('sulu_content.publish', '+1 day', 'ThisClass', 1, 'de'),
            $this->testPost('sulu_content.publish', '+1 day', 'ThisClass', 1, 'en'),
            $this->testPost('sulu_content.publish', '+1 day', 'ThisClass', 1, 'de'),
        ];


        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks?locale=de');
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
                    'taskName' => $items[$i]['taskName'],
                    'schedule' => $items[$i]['schedule'],
                ],
                $embedded
            );
        }
    }

    public function testCGetWithEntity()
    {
        $postData = [
            $this->testPost('sulu_content.publish', '+1 day', 'ThisClass', 1),
            $this->testPost('sulu_content.publish', '+1 day', 'ThisClass', 2),
            $this->testPost('sulu_content.publish', '+1 day', 'OtherClass', 1),
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

    public function testPost(
        $taskName = 'sulu_content.publish',
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
                'taskName' => $taskName,
                'schedule' => $scheduleDate,
                'entityClass' => $entityClass,
                'entityId' => $entityId,
                'locale' => $locale,
            ]
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($taskName, $responseData['taskName']);
        $this->assertEquals($scheduleDate, $responseData['schedule']);
        $this->assertEquals($locale, $responseData['locale']);

        return $responseData;
    }

    public function testPut($taskName = 'sulu_content.unpublish', $schedule = '+2 day')
    {
        $postData = $this->testPost();

        $date = new \DateTime($schedule);
        $scheduleDate = $date->format('Y-m-d\TH:i:sO');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/tasks/' . $postData['id'],
            ['taskName' => $taskName, 'schedule' => $scheduleDate]
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($postData['id'], $responseData['id']);
        $this->assertEquals($taskName, $responseData['taskName']);
        $this->assertEquals($scheduleDate, $responseData['schedule']);
    }

    public function testGet()
    {
        $postData = $this->testPost();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks/' . $postData['id']);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($postData['id'], $responseData['id']);
        $this->assertEquals($postData['taskName'], $responseData['taskName']);
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
            $this->testPost('sulu_content.publish'),
            $this->testPost('sulu_content.publish'),
            $this->testPost('sulu_content.publish'),
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
