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

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Cmf\Api\Slugifier\SlugifierInterface;

class CustomUrlControllerTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var PageDocument
     */
    private $contentDocument;

    /**
     * @var SlugifierInterface
     */
    private $slugifier;

    protected function setUp()
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->contentDocument = $this->documentManager->find('/cmf/sulu_io/contents', 'en');
        $this->slugifier = $this->getContainer()->get('sulu_document_manager.slugifier');
    }

    public function postProvider()
    {
        return [
            [
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-2']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-2',
            ],
            [
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-2']],
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-2',
            ],
            [
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-1', 'suffix' => []],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io',
            ],
            [
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['prefix' => 'test-1', 'suffix' => []],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io',
                400,
                9003,
            ],
            [
                [
                    'title' => 'Тестовий Заголовок Ґ Є І Ї',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-2']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-2',
            ],
            [
                [
                    'title' => 'Тестовий Заголовок Ґ Є І Ї',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-2']],
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-2',
            ],
        ];
    }

    /**
     * @dataProvider postProvider
     */
    public function testPost($data, $url, $statusCode = 200, $restErrorCode = null)
    {
        // content document is not there in provider
        if (array_key_exists('targetDocument', $data)) {
            $data['targetDocument'] = ['uuid' => $this->contentDocument->getUuid()];
        }

        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/webspaces/sulu_io/custom-urls', $data);

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertHttpStatusCode($statusCode, $response);

        if ($statusCode !== 200) {
            $this->assertEquals($restErrorCode, $responseData['code']);

            return;
        }

        foreach ($data as $key => $value) {
            if ($key === 'targetDocument') {
                $this->assertEquals($value['uuid'], $responseData[$key]['id'], $key);
            } else {
                $this->assertEquals($value, $responseData[$key], $key);
            }
        }

        $this->assertArrayHasKey('uuid', $responseData);
        $this->assertArrayHasKey('creator', $responseData);
        $this->assertArrayHasKey('changer', $responseData);

        $this->assertLessThanOrEqual(new \DateTime(), new \DateTime($responseData['created']));
        $this->assertLessThanOrEqual(new \DateTime(), new \DateTime($responseData['changed']));
        $this->assertEquals($this->slugifier->slugify($data['title']), $responseData['nodeName']);
        if (array_key_exists('targetDocument', $data)) {
            $this->assertEquals('Homepage', $responseData['targetTitle']);
        } else {
            $this->assertArrayNotHasKey('targetTitle', $responseData);
        }
        $this->assertEquals($url, $responseData['customUrl']);

        $this->assertArrayHasKey($url, $responseData['routes']);
        $this->assertFalse($responseData['routes'][$url]['history']);

        return $responseData['uuid'];
    }

    public function postMultipleProvider()
    {
        return [
            [
                [
                    'title' => 'Test-1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-11', 'suffix' => ['test-21']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                [
                    'title' => 'Test-2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-12', 'suffix' => ['test-22']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-11.sulu.io/test-21',
                'test-12.sulu.io/test-22',
            ],
            [
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-11', 'suffix' => ['test-21']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-12', 'suffix' => ['test-22']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-11.sulu.io/test-21',
                'test-12.sulu.io/test-22',
                400,
                9001,
            ],
            [
                [
                    'title' => 'Test-1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test', 'suffix' => ['test']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                [
                    'title' => 'Test-2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test', 'suffix' => ['test']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test.sulu.io/test',
                'test.sulu.io/test',
                409,
                1103,
            ],
            [
                [
                    'title' => 'Тестовий Заголовок Ґ Є І Ї 1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-11', 'suffix' => ['test-21']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                [
                    'title' => 'Тестовий Заголовок Ґ Є І Ї 2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-12', 'suffix' => ['test-22']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-11.sulu.io/test-21',
                'test-12.sulu.io/test-22',
            ],
        ];
    }

    /**
     * @dataProvider postMultipleProvider
     */
    public function testPostMultiple($before, $data, $beforeUrl, $url, $statusCode = 200, $restErrorCode = null)
    {
        $this->testPost($before, $beforeUrl);
        $this->testPost($data, $url, $statusCode, $restErrorCode);
    }

    public function putProvider()
    {
        return [
            [
                [
                    'test-11.sulu.io/test-21' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-11', 'suffix' => ['test-21']],
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
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-12', 'suffix' => ['test-22']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-12.sulu.io/test-22',
            ],
            [
                [
                    'test-11.sulu.io/test-21' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-11', 'suffix' => ['test-21']],
                        'targetDocument' => true,
                        'targetLocale' => 'de',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-12', 'suffix' => ['test-22']],
                    'targetDocument' => true,
                    'targetLocale' => 'de',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-12.sulu.io/test-22',
            ],
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-1',
            ],
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-2', 'suffix' => []],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-2.sulu.io',
            ],
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io/*',
                    'domainParts' => ['prefix' => 'test-1', 'suffix' => []],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io',
                400,
                9003,
            ],
            [
                [
                    'test.sulu.io/test' => [
                        'title' => 'Test',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test', 'suffix' => ['test']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-1',
                400,
                9001,
            ],
            [
                [
                    'test.sulu.io/test' => [
                        'title' => 'Test',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test', 'suffix' => ['test']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Test-1',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test', 'suffix' => ['test']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-1',
                409,
                1103,
            ],
            [
                [
                    'test-11.sulu.io/test-21' => [
                        'title' => 'Тестовий Заголовок Ґ Є І Ї 1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-11', 'suffix' => ['test-21']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Тестовий Заголовок Ґ Є І Ї 2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-12', 'suffix' => ['test-22']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-12.sulu.io/test-22',
            ],
        ];
    }

    /**
     * @dataProvider putProvider
     */
    public function testPut(array $before, $data, $url, $statusCode = 200, $restErrorCode = null)
    {
        foreach ($before as $beforeUrl => $beforeData) {
            $uuid = $this->testPost($beforeData, $beforeUrl);
        }

        // content document is not there in provider
        if (array_key_exists('targetDocument', $data)) {
            $data['targetDocument'] = ['uuid' => $this->contentDocument->getUuid()];
        }

        $client = $this->createAuthenticatedClient();

        $client->request('PUT', '/api/webspaces/sulu_io/custom-urls/' . $uuid, $data);

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertHttpStatusCode($statusCode, $response);

        if ($statusCode !== 200) {
            $this->assertEquals($restErrorCode, $responseData['code']);

            return;
        }

        foreach ($data as $key => $value) {
            if ($key === 'targetDocument') {
                $this->assertEquals($value['uuid'], $responseData[$key]['id'], $key);
            } else {
                $this->assertEquals($value, $responseData[$key], $key);
            }
        }

        $this->assertEquals($uuid, $responseData['uuid']);
        $this->assertArrayHasKey('creator', $responseData);
        $this->assertArrayHasKey('changer', $responseData);

        $this->assertLessThanOrEqual(new \DateTime(), new \DateTime($responseData['created']));
        $this->assertLessThanOrEqual(new \DateTime(), new \DateTime($responseData['changed']));
        $this->assertEquals($this->slugifier->slugify($data['title']), $responseData['nodeName']);
        if (array_key_exists('targetDocument', $data)) {
            $this->assertEquals('Homepage', $responseData['targetTitle']);
        } else {
            $this->assertArrayNotHasKey('targetTitle', $responseData);
        }
        $this->assertEquals($url, $responseData['customUrl']);

        $this->assertArrayHasKey($beforeUrl, $responseData['routes']);
        $this->assertArrayHasKey($url, $responseData['routes']);

        if ($beforeUrl !== $url) {
            $this->assertTrue($responseData['routes'][$beforeUrl]['history']);
        }
        $this->assertFalse($responseData['routes'][$url]['history']);

        return $responseData['uuid'];
    }

    public function getProvider()
    {
        return [
            [
                [
                    'title' => 'Test',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-2']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-2',
            ],
            [
                [
                    'title' => 'Тестовий Заголовок Ґ Є І Ї',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-2']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-1.sulu.io/test-2',
            ],
        ];
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet($data, $url)
    {
        // content document is not there in provider
        if (array_key_exists('targetDocument', $data)) {
            $data['targetDocument'] = ['uuid' => $this->contentDocument->getUuid()];
        }

        $uuid = $this->testPost($data, $url);

        $dateTime = new \DateTime();
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid . '?locale=en');

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertHttpStatusCode(200, $response);

        foreach ($data as $key => $value) {
            if ($key === 'targetDocument') {
                $this->assertEquals($value['uuid'], $responseData[$key]['id'], $key);
            } else {
                $this->assertEquals($value, $responseData[$key], $key);
            }
        }

        $this->assertEquals($uuid, $responseData['uuid']);
        $this->assertArrayHasKey('creator', $responseData);
        $this->assertArrayHasKey('changer', $responseData);

        $this->assertGreaterThanOrEqual(new \DateTime($responseData['created']), $dateTime);
        $this->assertGreaterThanOrEqual(new \DateTime($responseData['changed']), $dateTime);
        $this->assertEquals($this->slugifier->slugify($data['title']), $responseData['nodeName']);
        if (array_key_exists('targetDocument', $data)) {
            $this->assertEquals('Homepage', $responseData['targetTitle']);
        } else {
            $this->assertArrayNotHasKey('targetTitle', $responseData);
        }
        $this->assertEquals($url, $responseData['customUrl']);

        $this->assertArrayHasKey($url, $responseData['routes']);
        $this->assertFalse($responseData['routes'][$url]['history']);
    }

    /**
     * @dataProvider getProvider
     */
    public function testGetWithoutLocale($data, $url)
    {
        // content document is not there in provider
        if (array_key_exists('targetDocument', $data)) {
            $data['targetDocument'] = ['uuid' => $this->contentDocument->getUuid()];
        }

        $uuid = $this->testPost($data, $url);

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid);

        $response = $client->getResponse();

        $this->assertHttpStatusCode(400, $response);
    }

    public function cgetProvider()
    {
        return [
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                    'test-2.sulu.io/test-2' => [
                        'title' => 'Test-2',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-2', 'suffix' => ['test-2']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
            ],
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Тестовий Заголовок Ґ Є І Ї 1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                    'test-2.sulu.io/test-2' => [
                        'title' => 'Тестовий Заголовок Ґ Є І Ї 2',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-2', 'suffix' => ['test-2']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider cgetProvider
     */
    public function testCGet($items)
    {
        foreach ($items as $url => $data) {
            $items[$url]['uuid'] = $this->testPost($data, $url);
        }

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/webspaces/sulu_io/custom-urls?locale=en');
        $requestTime = new \DateTime();

        $response = $client->getResponse();
        $responseDataComplete = json_decode($response->getContent(), true);

        $this->assertHttpStatusCode(200, $response);

        foreach ($responseDataComplete['_embedded']['custom-urls'] as $responseData) {
            $data = $items[$responseData['customUrl']];

            foreach (['uuid', 'title', 'published', 'baseDomain'] as $key) {
                $this->assertEquals($data[$key], $responseData[$key]);
            }

            $this->assertEquals($this->contentDocument->getUuid(), $responseData['targetDocument']);

            $this->assertArrayHasKey('creator', $responseData);
            $this->assertArrayHasKey('changer', $responseData);

            $this->assertLessThanOrEqual($requestTime, new \DateTime($responseData['created']));
            $this->assertLessThanOrEqual($requestTime, new \DateTime($responseData['changed']));
            if (array_key_exists('targetDocument', $data)) {
                $this->assertEquals('Homepage', $responseData['targetTitle']);
            } else {
                $this->assertArrayNotHasKey('targetTitle', $responseData);
            }
        }
    }

    /**
     * @dataProvider cgetProvider
     */
    public function testCGetWithoutLocale($items)
    {
        foreach ($items as $url => $data) {
            $items[$url]['uuid'] = $this->testPost($data, $url);
        }

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/webspaces/sulu_io/custom-urls');

        $response = $client->getResponse();

        $this->assertHttpStatusCode(400, $response);
    }

    /**
     * @dataProvider getProvider
     */
    public function testDelete($data, $url)
    {
        $uuid = $this->testPost($data, $url);

        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/webspaces/sulu_io/custom-urls/' . $uuid);

        $response = $client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid . '?locale=en');

        $response = $client->getResponse();
        $this->assertHttpStatusCode(404, $response);
    }

    /**
     * @dataProvider cgetProvider
     */
    public function testCDelete($items)
    {
        $uuid = $this->testPost(
            [
                'title' => 'Test',
                'published' => true,
                'baseDomain' => '*.sulu.io',
                'domainParts' => ['prefix' => 'test', 'suffix' => ['test']],
                'targetDocument' => true,
                'targetLocale' => 'en',
                'canonical' => true,
                'redirect' => true,
                'noFollow' => true,
                'noIndex' => true,
            ],
            'test.sulu.io/test'
        );

        $uuids = [];
        foreach ($items as $url => $data) {
            $uuids[] = $this->testPost($data, $url);
        }

        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/webspaces/sulu_io/custom-urls?ids=' . implode(',', $uuids));

        $response = $client->getResponse();
        $this->assertHttpStatusCode(204, $response);

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/webspaces/sulu_io/custom-urls?locale=en');

        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(1, $responseData['_embedded']['custom-urls']);
        $this->assertEquals($uuid, $responseData['_embedded']['custom-urls'][0]['uuid']);
    }

    public function cdeleteRoutesProvider()
    {
        return [
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
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
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-2', 'suffix' => ['test-2']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-2.sulu.io/test-2',
                [],
                ['test-2.sulu.io/test-2', 'test-1.sulu.io/test-1'],
            ],
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
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
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-2', 'suffix' => ['test-2']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-2.sulu.io/test-2',
                ['test-1.sulu.io/test-1'],
                ['test-2.sulu.io/test-2'],
            ],
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Test-1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
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
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-2', 'suffix' => ['test-2']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-2.sulu.io/test-2',
                ['test-2.sulu.io/test-2'],
                ['test-1.sulu.io/test-1'],
                420,
                9000,
            ],
            [
                [
                    'test-1.sulu.io/test-1' => [
                        'title' => 'Тестовий Заголовок Ґ Є І Ї 1',
                        'published' => true,
                        'baseDomain' => '*.sulu.io',
                        'domainParts' => ['prefix' => 'test-1', 'suffix' => ['test-1']],
                        'targetDocument' => true,
                        'targetLocale' => 'en',
                        'canonical' => true,
                        'redirect' => true,
                        'noFollow' => true,
                        'noIndex' => true,
                    ],
                ],
                [
                    'title' => 'Тестовий Заголовок Ґ Є І Ї 2',
                    'published' => true,
                    'baseDomain' => '*.sulu.io',
                    'domainParts' => ['prefix' => 'test-2', 'suffix' => ['test-2']],
                    'targetDocument' => true,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => true,
                    'noFollow' => true,
                    'noIndex' => true,
                ],
                'test-2.sulu.io/test-2',
                [],
                ['test-2.sulu.io/test-2', 'test-1.sulu.io/test-1'],
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
        $uuid = $this->testPut($before, $data, $url);

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid . '?locale=en');
        $customUrl = json_decode($client->getResponse()->getContent(), true);

        $uuids = [];
        foreach ($delete as $item) {
            $uuids[] = $customUrl['routes'][$item]['uuid'];
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

        $client->request('GET', '/api/webspaces/sulu_io/custom-urls/' . $uuid . '?locale=en');
        $customUrl = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($excpected, array_keys($customUrl['routes']));
    }
}
