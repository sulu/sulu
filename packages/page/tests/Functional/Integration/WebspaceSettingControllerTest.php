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

namespace Sulu\Page\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\TestWith;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Tests\Traits\CreateWebspaceSettingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class WebspaceSettingControllerTest extends SuluTestCase
{
    use CreateWebspaceSettingTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );

        self::purgeDatabase();
    }

    public function testGetBeforeTheFirstSaveReturnsAnEmptyForm(): void
    {
        $content = $this->request('GET', '/admin/api/webspace-settings/sulu-io?locale=en');

        $this->assertSame('sulu-io', $content['id']);
        $this->assertSame('sulu-io', $content['webspace']);
        $this->assertSame('webspace_settings_sulu_io', $content['template']);
        $this->assertSame('en', $content['locale']);
        $this->assertSame('unpublished', $content['workflowPlace']);
        $this->assertArrayHasKey('_permissions', $content);

        // nothing is stored by loading the form
        /** @var numeric-string $settingsCount */
        $settingsCount = self::getEntityManager()->getConnection()->fetchOne('SELECT COUNT(*) FROM pa_webspace_settings');
        $this->assertSame(0, (int) $settingsCount);
    }

    public function testGetWebspaceWithoutSettingsFormIsNotFound(): void
    {
        $this->client->request('GET', '/admin/api/webspace-settings/blog?locale=en');

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPutCreatesTheSettingsWithTheFormOfTheWebspace(): void
    {
        $content = $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en', [
            'companyName' => 'Sulu GmbH',
            'email' => 'hello@sulu.io',
            'copyright' => '© Sulu',
        ]);

        $this->assertSame('sulu-io', $content['id']);
        $this->assertSame('webspace_settings_sulu_io', $content['template']);
        $this->assertSame('Sulu GmbH', $content['companyName']);
        $this->assertSame('hello@sulu.io', $content['email']);
        $this->assertSame(['en'], $content['availableLocales']);
        $this->assertSame('unpublished', $content['workflowPlace']);
        $this->assertArrayNotHasKey('name', $content);
        $this->assertArrayNotHasKey('key', $content);

        $content = $this->request('GET', '/admin/api/webspace-settings/sulu-io?locale=en');

        $this->assertSame('Sulu GmbH', $content['companyName']);
    }

    public function testPutTwiceKeepsASingleSetting(): void
    {
        $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en', ['companyName' => 'Sulu GmbH']);

        $content = $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=de', ['companyName' => 'Sulu GmbH DE']);

        $this->assertSame(['en', 'de'], $content['availableLocales']);
        /** @var numeric-string $settingsCount */
        $settingsCount = self::getEntityManager()->getConnection()->fetchOne('SELECT COUNT(*) FROM pa_webspace_settings');
        $this->assertSame(1, (int) $settingsCount);
    }

    public function testPutWebspaceWithoutSettingsFormIsNotFound(): void
    {
        $this->client->request('PUT', '/admin/api/webspace-settings/blog?locale=en', [], [], [], (string) \json_encode(['companyName' => 'Blog']));

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPutPublish(): void
    {
        $content = $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en&action=publish', ['companyName' => 'Sulu GmbH']);

        $this->assertSame('published', $content['workflowPlace']);
        $this->assertTrue($content['publishedState']);
    }

    public function testGetOtherLocaleIsAGhost(): void
    {
        $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en', ['companyName' => 'Sulu GmbH']);

        $content = $this->request('GET', '/admin/api/webspace-settings/sulu-io?locale=de');

        $this->assertSame('en', $content['ghostLocale']);
        $this->assertSame(['en'], $content['availableLocales']);
    }

    public function testCopyLocale(): void
    {
        $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en', ['companyName' => 'Sulu GmbH']);

        $this->client->request('POST', '/admin/api/webspace-settings/sulu-io?locale=en&action=copy_locale&src=en&dest=de');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $content = $this->request('GET', '/admin/api/webspace-settings/sulu-io?locale=de');

        $this->assertSame('Sulu GmbH', $content['companyName']);
        $this->assertSame(['en', 'de'], $content['availableLocales']);
    }

    public function testUnpublish(): void
    {
        $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en&action=publish', ['companyName' => 'Sulu GmbH']);

        $this->client->request('POST', '/admin/api/webspace-settings/sulu-io?locale=en&action=unpublish');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $content = $this->request('GET', '/admin/api/webspace-settings/sulu-io?locale=en');

        $this->assertSame('unpublished', $content['workflowPlace']);
        $this->assertFalse($content['publishedState']);
    }

    public function testGetVersions(): void
    {
        self::createWebspaceSetting('sulu-io', [
            'en' => ['live' => ['companyName' => 'Sulu GmbH']],
        ]);
        $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en&action=publish', ['companyName' => 'Sulu']);

        $this->client->request('GET', '/admin/api/webspace-settings/sulu-io/versions?page=1&locale=en&fields=version,changer,id');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var array{_embedded: array{webspace_settings_versions: array<int, array{id: string}>}, total: int} $versions */
        $versions = \json_decode((string) $this->client->getResponse()->getContent(), true);

        $this->assertGreaterThanOrEqual(1, $versions['total']);
        $this->assertSame('sulu-io', $versions['_embedded']['webspace_settings_versions'][0]['id']);
    }

    public function testRestoreVersion(): void
    {
        $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en&action=publish', ['companyName' => 'Version 1']);
        \sleep(1); // versions are identified by their timestamp
        $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en&action=publish', ['companyName' => 'Version 2']);

        $this->client->request('GET', '/admin/api/webspace-settings/sulu-io/versions?page=1&locale=en&fields=version,id');

        /** @var array{_embedded: array{webspace_settings_versions: array<int, array{version: int}>}} $versions */
        $versions = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $oldestVersion = $versions['_embedded']['webspace_settings_versions'][\count($versions['_embedded']['webspace_settings_versions']) - 1]['version'];

        $this->client->request('POST', '/admin/api/webspace-settings/sulu-io?locale=en&action=restore&version=' . $oldestVersion);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $content = $this->request('GET', '/admin/api/webspace-settings/sulu-io?locale=en');

        $this->assertSame('Version 1', $content['companyName']);
    }

    #[TestWith(['copy_locale&src=en&dest=de'])]
    #[TestWith(['unpublish'])]
    #[TestWith(['publish'])]
    public function testTriggerBeforeTheFirstSaveIsNotFound(string $action): void
    {
        $this->client->request('POST', '/admin/api/webspace-settings/sulu-io?locale=en&action=' . $action);

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testTriggerWebspaceWithoutSettingsFormIsNotFound(): void
    {
        $this->client->request('POST', '/admin/api/webspace-settings/blog?locale=en&action=publish');

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testCopyLocaleSplitsTheTargetLocales(): void
    {
        $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en', ['companyName' => 'Sulu GmbH']);

        $this->client->request('POST', '/admin/api/webspace-settings/sulu-io?locale=en&action=copy_locale&src=en&dest=de,en');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $content = $this->request('GET', '/admin/api/webspace-settings/sulu-io?locale=de');

        $this->assertSame(['en', 'de'], $content['availableLocales'], 'the target locales are copied one by one, not as the locale "de,en"');
    }

    #[TestWith(['zz'])]
    #[TestWith(['de,zz'])]
    #[TestWith([''])]
    public function testCopyLocaleToInvalidLocalesIsABadRequest(string $destinationLocales): void
    {
        $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en', ['companyName' => 'Sulu GmbH']);

        $this->client->request('POST', '/admin/api/webspace-settings/sulu-io?locale=en&action=copy_locale&src=en&dest=' . $destinationLocales);
        $this->assertHttpStatusCode(400, $this->client->getResponse());

        $content = $this->request('GET', '/admin/api/webspace-settings/sulu-io?locale=en');
        $this->assertSame(['en'], $content['availableLocales'], 'nothing is copied when one of the locales is invalid');
    }

    public function testRestoreWithoutVersionIsABadRequest(): void
    {
        $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en', ['companyName' => 'Sulu GmbH']);

        $this->client->request('POST', '/admin/api/webspace-settings/sulu-io?locale=en&action=restore');

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testRestoreOnlyWritesTheDraft(): void
    {
        $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en&action=publish', ['companyName' => 'Version 1']);
        \sleep(1); // versions are identified by their timestamp
        $this->request('PUT', '/admin/api/webspace-settings/sulu-io?locale=en&action=publish', ['companyName' => 'Version 2']);

        $this->client->request('GET', '/admin/api/webspace-settings/sulu-io/versions?page=1&locale=en&fields=version,id');

        /** @var array{_embedded: array{webspace_settings_versions: array<int, array{version: int}>}} $versions */
        $versions = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $oldestVersion = $versions['_embedded']['webspace_settings_versions'][\count($versions['_embedded']['webspace_settings_versions']) - 1]['version'];

        $this->client->request('POST', '/admin/api/webspace-settings/sulu-io?locale=en&action=restore&stage=live&version=' . $oldestVersion);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $content = $this->request('GET', '/admin/api/webspace-settings/sulu-io?locale=en');
        $this->assertSame('Version 1', $content['companyName']);

        self::getEntityManager()->clear();
        $live = self::getContainer()->get('sulu_page.webspace_settings_twig_extension')->loadWebspaceSettings(null, 'sulu-io', 'en');
        $this->assertIsArray($live);
        $this->assertIsArray($live['content'] ?? null);
        $this->assertSame('Version 2', $live['content']['companyName'], 'the live content is only changed by publishing');
    }

    public function testHasNoDeleteAndNoListRoute(): void
    {
        $this->client->request('DELETE', '/admin/api/webspace-settings/sulu-io?locale=en');
        $this->assertHttpStatusCode(405, $this->client->getResponse());

        $this->client->request('GET', '/admin/api/webspace-settings?locale=en');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    /**
     * @param array<string, mixed>|null $data
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $uri, ?array $data = null): array
    {
        $this->client->request($method, $uri, [], [], [], null === $data ? null : (string) \json_encode($data));

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> */
        return \json_decode((string) $response->getContent(), true);
    }
}
