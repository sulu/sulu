<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Controller;

use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AdminControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
    }

    public function testGetConfig(): void
    {
        $this->initPhpcr();
        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());

        $this->client->jsonRequest('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent() ?: '');

        $this->assertObjectHasAttribute('sulu_admin', $response);
        $this->assertObjectHasAttribute('navigation', $response->sulu_admin);
        $this->assertObjectHasAttribute('resources', $response->sulu_admin);
        $this->assertObjectHasAttribute('routes', $response->sulu_admin);
        $this->assertObjectHasAttribute('fieldTypeOptions', $response->sulu_admin);
        $this->assertIsArray($response->sulu_admin->navigation);
        $this->assertIsArray($response->sulu_admin->routes);
        $this->assertIsObject($response->sulu_admin->resources);
        $this->assertObjectHasAttribute('sulu_preview', $response);

        $this->assertEquals('en', $response->sulu_admin->localizations[0]->localization);
        $this->assertEquals('en_us', $response->sulu_admin->localizations[1]->localization);
        $this->assertEquals('de', $response->sulu_admin->localizations[2]->localization);
        $this->assertEquals('de_at', $response->sulu_admin->localizations[3]->localization);
    }

    public function testGetConfigWithFallbackNonExistUserLocale(): void
    {
        $this->initPhpcr();

        $this->getTestUser()->setLocale('not-exist');

        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());

        $this->client->jsonRequest('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent() ?: '');

        $this->assertObjectHasAttribute('sulu_admin', $response);
        $this->assertObjectHasAttribute('navigation', $response->sulu_admin);
        $this->assertObjectHasAttribute('resources', $response->sulu_admin);
        $this->assertObjectHasAttribute('routes', $response->sulu_admin);
        $this->assertObjectHasAttribute('fieldTypeOptions', $response->sulu_admin);
        $this->assertIsArray($response->sulu_admin->navigation);
        $this->assertIsArray($response->sulu_admin->routes);
        $this->assertIsObject($response->sulu_admin->resources);
        $this->assertObjectHasAttribute('sulu_preview', $response);

        $this->assertEquals('en', $response->sulu_admin->localizations[0]->localization);
        $this->assertEquals('en_us', $response->sulu_admin->localizations[1]->localization);
        $this->assertEquals('de', $response->sulu_admin->localizations[2]->localization);
        $this->assertEquals('de_at', $response->sulu_admin->localizations[3]->localization);
    }

    public function testTemplateConfig(): void
    {
        $this->initPhpcr();
        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());

        $this->client->request('GET', '/admin/');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $html = $response->getContent();
        $this->assertIsString($html);

        // extract json from html
        $html = \explode('SULU_CONFIG = Object.freeze(', $html, 2)[1] ?? null;
        $this->assertIsString($html, 'Could not extract "Sulu_CONFIG" from response object.');
        $json = \explode(');', $html, 2)[0] ?? null;
        $this->assertIsString($json, 'Could not find end of "SULU_CONFIG" in the response content.');
        $config = \json_decode($json, true);

        // test config object
        $this->assertIsArray($config, 'Extracted "SULU_CONFIG" is not a valid json object.');
        $this->assertSame([
            'initialLoginState' => true,
            'translations' => [
                'de',
                'en',
            ],
            'fallbackLocale' => 'en',
            'endpoints' => [
                'config' => '/admin/config',
                'items' => '/admin/api/items',
                'loginCheck' => '/admin/login',
                'logout' => '/admin/logout',
                'profileSettings' => '/admin/api/profile/settings',
                'forgotPasswordReset' => '/admin/security/reset/email',
                'resetPassword' => '/admin/security/reset',
                'translations' => '/admin/translations',
                'generateUrl' => '/admin/api/resourcelocators?action=generate',
                'routing' => '/admin/js/routing',
            ],
            'suluVersion' => '_._._',
            'appVersion' => null,
            'passwordPattern' => null,
            'passwordInfoTranslationKey' => null,
        ], $config);
    }

    public function testGetNotExistingMetdata(): void
    {
        $this->client->jsonRequest('GET', '/admin/metadata/test1/test');

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGetMetaDataKeysOnly(): void
    {
        $this->initPhpcr();
        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());

        $this->client->request('GET', '/admin/metadata/form/page?onlyKeys=true');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $json = $response->getContent();
        $this->assertIsString($json);

        $metaData = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        $this->assertEquals([
            'types' => [
                'default' => [],
                'overview' => [],
            ],
            'defaultType' => null,
        ], $metaData);
    }
}
