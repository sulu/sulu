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

namespace Sulu\Page\Tests\Functional\Infrastructure\Symfony\Twig;

use PHPUnit\Framework\Attributes\TestWith;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Infrastructure\Symfony\Twig\Extension\WebspaceSettingsTwigExtension;
use Sulu\Page\Tests\Traits\CreateWebspaceSettingTrait;

class WebspaceSettingsTwigExtensionTest extends SuluTestCase
{
    use CreateWebspaceSettingTrait;

    private WebspaceSettingsTwigExtension $twigExtension;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->twigExtension = self::getContainer()->get('sulu_page.webspace_settings_twig_extension');

        self::createWebspaceSetting('sulu-io', [
            'en' => [
                'live' => ['companyName' => 'Sulu GmbH', 'email' => 'hello@sulu.io', 'copyright' => '© Sulu'],
                'draft' => ['companyName' => 'Sulu GmbH (draft)', 'email' => 'hello@sulu.io', 'copyright' => '© Sulu'],
            ],
            'de' => [
                'live' => ['companyName' => 'Sulu GmbH DE', 'email' => 'hallo@sulu.io', 'copyright' => '© Sulu'],
            ],
            'fr' => [
                'draft' => ['companyName' => 'Sulu GmbH FR (draft only)'],
            ],
        ]);
    }

    #[TestWith(['en', 'Sulu GmbH'])]
    #[TestWith(['de', 'Sulu GmbH DE'])]
    public function testLoad(string $locale, string $expectedCompanyName): void
    {
        /** @var array{content: array<string, mixed>} $result */
        $result = $this->twigExtension->loadWebspaceSettings(null, 'sulu-io', $locale);

        $this->assertSame($expectedCompanyName, $result['content']['companyName']);
    }

    public function testLoadWithProperties(): void
    {
        $result = $this->twigExtension->loadWebspaceSettings(['companyName' => 'companyName'], 'sulu-io', 'en');

        $this->assertIsArray($result);
        $this->assertSame('Sulu GmbH', $result['companyName']);
        $this->assertArrayNotHasKey('email', $result);
    }

    public function testLoadUnpublishedLocaleReturnsNull(): void
    {
        $this->assertNull($this->twigExtension->loadWebspaceSettings(null, 'sulu-io', 'fr'));
    }

    public function testLoadWebspaceWithoutSettingsReturnsNull(): void
    {
        $this->assertNull($this->twigExtension->loadWebspaceSettings(null, 'blog', 'en'));
    }

    public function testLoadReturnsNullWhenTheSettingsUseAFormWhichIsNoLongerConfigured(): void
    {
        self::getEntityManager()->getConnection()->executeStatement(
            "UPDATE pa_webspace_setting_contents SET templateKey = 'renamed_form' WHERE webspaceKey = 'sulu-io'",
        );
        self::getEntityManager()->clear();

        $this->assertNull($this->twigExtension->loadWebspaceSettings(null, 'sulu-io', 'en'));
    }

    public function testLoadSeveralLocalesOfTheSameWebspaceInOneRequest(): void
    {
        // a request starts without the settings in memory
        self::getEntityManager()->clear();

        /** @var array{content: array<string, mixed>} $english */
        $english = $this->twigExtension->loadWebspaceSettings(null, 'sulu-io', 'en');
        /** @var array{content: array<string, mixed>} $german */
        $german = $this->twigExtension->loadWebspaceSettings(null, 'sulu-io', 'de');

        $this->assertSame('Sulu GmbH', $english['content']['companyName']);
        $this->assertSame('Sulu GmbH DE', $german['content']['companyName']);
    }

    public function testLoadsTheSettingsOnlyOncePerRequest(): void
    {
        $this->twigExtension->loadWebspaceSettings(null, 'sulu-io', 'en');

        self::createWebspaceSetting('sulu-io', [
            'en' => ['live' => ['companyName' => 'Changed in the meantime']],
        ]);

        /** @var array{content: array<string, mixed>} $result */
        $result = $this->twigExtension->loadWebspaceSettings(null, 'sulu-io', 'en');
        $this->assertSame('Sulu GmbH', $result['content']['companyName']);

        $this->twigExtension->reset();

        /** @var array{content: array<string, mixed>} $result */
        $result = $this->twigExtension->loadWebspaceSettings(null, 'sulu-io', 'en');
        $this->assertSame('Changed in the meantime', $result['content']['companyName']);
    }

    public function testLoadTagsTheWebspace(): void
    {
        $this->twigExtension->loadWebspaceSettings(null, 'sulu-io', 'en');

        /** @var ReferenceStoreInterface $referenceStore */
        $referenceStore = self::getContainer()->get('sulu_http_cache.reference_store');

        $this->assertContains('webspace_settings-sulu-io', $referenceStore->getAll());
    }
}
