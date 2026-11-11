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

use CmsIg\Seal\Reindex\ReindexConfig;
use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;
use Sulu\Page\Infrastructure\Sulu\Reference\WebspaceSettingReferenceRefresher;
use Sulu\Page\Infrastructure\Sulu\Search\AdminWebspaceSettingReindexProvider;
use Sulu\Page\Tests\Traits\CreateWebspaceSettingTrait;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class WebspaceSettingIntegrationTest extends SuluTestCase
{
    use CreateWebspaceSettingTrait;

    private const PRIVACY_PAGE_UUID = '0199e4c8-4b3a-7c1d-9f2e-5a6b7c8d9e0f';

    protected function setUp(): void
    {
        self::purgeDatabase();
    }

    public function testReferencesAreWritten(): void
    {
        self::createWebspaceSetting('sulu-io', [
            'en' => ['live' => ['companyName' => 'Sulu GmbH', 'privacyPage' => self::PRIVACY_PAGE_UUID]],
        ]);

        /** @var WebspaceSettingReferenceRefresher $referenceRefresher */
        $referenceRefresher = self::getContainer()->get('sulu_page.webspace_setting_reference_refresher');
        \iterator_to_array($referenceRefresher->refresh());
        self::getEntityManager()->flush();

        /** @var ReferenceRepositoryInterface $referenceRepository */
        $referenceRepository = self::getContainer()->get('sulu_reference.reference_repository');
        $references = \iterator_to_array($referenceRepository->findFlatBy(
            [
                'referenceResourceKey' => WebspaceSettingInterface::RESOURCE_KEY,
                'referenceResourceId' => 'sulu-io',
                'resourceKey' => 'pages',
                'resourceId' => self::PRIVACY_PAGE_UUID,
            ],
            ['referenceContext' => 'asc'],
            ['referenceTitle', 'referenceContext', 'referenceRouterAttributes'],
        ));

        $this->assertSame(
            [
                ['referenceTitle' => 'sulu-io', 'referenceContext' => 'draft', 'referenceRouterAttributes' => ['locale' => 'en', 'webspace' => 'sulu-io']],
                ['referenceTitle' => 'sulu-io', 'referenceContext' => 'live', 'referenceRouterAttributes' => ['locale' => 'en', 'webspace' => 'sulu-io']],
            ],
            $references,
        );
    }

    public function testReindexProviderUsesTheSecurityContextOfTheWebspace(): void
    {
        self::createWebspaceSetting('sulu-io', [
            'en' => ['draft' => ['companyName' => 'Sulu GmbH']],
        ]);

        /** @var AdminWebspaceSettingReindexProvider $reindexProvider */
        $reindexProvider = self::getContainer()->get('sulu_page.admin_webspace_setting_reindex_provider');
        $documents = \iterator_to_array($reindexProvider->provide(ReindexConfig::create()), false);

        $this->assertCount(1, $documents);
        $this->assertSame('webspace_settings__sulu-io__en', $documents[0]['id']);
        $this->assertSame('sulu-io', $documents[0]['title']);
        $this->assertSame(['webspaceKey' => 'sulu-io'], $documents[0]['metadata']);
        $this->assertSame('sulu.webspaces.sulu-io.webspace-settings', $documents[0]['securityContext']);

        $documents = \iterator_to_array($reindexProvider->provide(
            ReindexConfig::create()->withIdentifiers(['snippets__sulu-io__en']),
        ), false);

        $this->assertSame([], $documents);
    }
}
