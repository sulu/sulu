<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class CustomUrlRouteControllerTest extends SuluTestCase
{
    /**
     * @var PageDocument
     */
    private $contentDocument;

    protected function setUp()
    {
        $this->initPhpcr();
        $this->contentDocument = $this->getContainer()->get('sulu_document_manager.document_manager')
            ->find('/cmf/sulu_io/contents', 'en');
    }

    public function cdeleteRoutesProvider()
    {
        return [
            [
                [
                    [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-1', 'test-1'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-2', 'test-2'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-2.sulu.io/test-2',
                [],
                ['test-1.sulu.io/test-1'],
            ],
            [
                [
                    [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io/*',
                        'domainParts' => ['test-1', 'test-1'],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['test-2', 'test-2'],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-2.sulu.io/test-2',
                ['test-1.sulu.io/test-1'],
                [],
            ],
        ];
    }

    /**
     * @dataProvider cdeleteRoutesProvider
     */
    public function testCDeleteRoutes(
        array $before,
        $data,
        $url,
        $delete,
        $excpected,
        $statusCode = 204,
        $restErrorCode = null
    ) {
        $client = $this->createAuthenticatedClient();

        foreach ($before as $beforeData) {
            $beforeData['targetDocument'] = $this->contentDocument->getUuid();
            $client->request('POST', '/api/webspaces/sulu_io/custom-urls', $beforeData);
        }

        $data['targetDocument'] = $this->contentDocument->getUuid();

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $uuid = $responseData['id'];

        $client->request('PUT', '/api/webspaces/sulu_io/custom-urls/' . $uuid, $data);

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $uuid = $responseData['id'];

        $client->request('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid . '/routes');
        $response = json_decode($client->getResponse()->getContent(), true);

        $customUrlRoutes = $response['_embedded']['custom_url_routes'];

        $uuids = [];
        foreach ($delete as $item) {
            $customUrlRoute = array_filter($customUrlRoutes, function($customUrlRoute) use ($item) {
                return $customUrlRoute['resourcelocator'] === $item;
            });

            if (count($customUrlRoute) > 0) {
                $uuids[] = $customUrlRoute[0]['id'];
            }
        }

        $client->request(
            'DELETE',
            '/api/webspaces/sulu_io/custom-urls/' . $uuid . '/routes?ids=' . implode(',', $uuids)
        );

        $response = $client->getResponse();
        $this->assertHttpStatusCode($statusCode, $response);

        if ($restErrorCode) {
            $responseData = json_decode($response->getContent(), true);
            $this->assertEquals($restErrorCode, $responseData['code']);

            return;
        }

        $client->request('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid);
        $customUrl = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($url, $customUrl['customUrl']);

        $client->request('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid . '/routes');
        $customUrlRoutes = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(
            $excpected,
            array_map(function($route) {
                return $route['resourcelocator'];
            }, $customUrlRoutes['_embedded']['custom_url_routes'])
        );
    }
}
