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

namespace Sulu\Page\Tests\Functional\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\WebspaceSetting;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Sulu\Page\Infrastructure\Sulu\Admin\WebspaceSettingAdmin;

class WebspaceSettingAdminTest extends SuluTestCase
{
    public function testSecurityContextsOnlyForWebspacesWithWebspaceSettingsForm(): void
    {
        /** @var WebspaceSettingAdmin $admin */
        $admin = self::getContainer()->get('sulu_page.webspace_setting_admin');

        $this->assertSame(
            [
                'Sulu' => [
                    'Webspaces' => [
                        'sulu.webspaces.sulu-io.webspace-settings' => ['view', 'edit', 'live', 'review'],
                    ],
                ],
            ],
            $admin->getSecurityContexts(),
        );
    }

    public function testTheSettingsCarryTheSecurityContextOfTheAdmin(): void
    {
        $this->assertSame(
            WebspaceSettingAdmin::getSecurityContext('sulu-io'),
            (new WebspaceSetting('sulu-io'))->getSecurityContext(),
        );
    }

    public function testTabsViewIsAWebspaceTabShownForWebspacesWithWebspaceSettingsForm(): void
    {
        $tabsView = $this->getViewRegistry()->findViewByName(WebspaceSettingAdmin::TABS_VIEW);

        $this->assertSame(PageAdmin::WEBSPACE_TABS_VIEW, $tabsView->getParent());
        $this->assertSame(4096, $tabsView->getOption('tabOrder'));
        $this->assertSame('webspaceSettingsForm && settingsPermissions && settingsPermissions.view', $tabsView->getOption('webspaceCondition'));
        $this->assertSame('sulu_page.webspace_setting_tabs', $tabsView->getType());
    }

    public function testFormViewIsTheFirstTabAndPassesTheWebspaceToTheMetadata(): void
    {
        $contentView = $this->getViewRegistry()->findViewByName(WebspaceSettingAdmin::CONTENT_VIEW);

        $this->assertSame(WebspaceSettingAdmin::TABS_VIEW, $contentView->getParent());
        $this->assertSame(['webspace'], $contentView->getOption('routerAttributesToFormMetadata'));
        $this->assertSame('webspace_settings', $contentView->getOption('formKey'));
    }

    public function testVersionsAndActivityAreTabsNextToTheForm(): void
    {
        $this->assertSame(
            WebspaceSettingAdmin::TABS_VIEW,
            $this->getViewRegistry()->findViewByName(WebspaceSettingAdmin::VERSIONS_VIEW)->getParent(),
        );
        $this->assertSame(
            WebspaceSettingAdmin::TABS_VIEW,
            $this->getViewRegistry()->findViewByName(WebspaceSettingAdmin::ACTIVITY_VIEW)->getParent(),
        );
    }

    public function testHasNoSeoExcerptSettingsOrInsightsTabs(): void
    {
        foreach (['seo', 'excerpt', 'settings', 'insights'] as $tab) {
            try {
                $this->getViewRegistry()->findViewByName(WebspaceSettingAdmin::TABS_VIEW . '.' . $tab);
                $this->fail(\sprintf('The tab "%s" should not exist.', $tab));
            } catch (ViewNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testFormViewToolbarHasNoDeleteAndNoTypeAction(): void
    {
        $contentView = $this->getViewRegistry()->findViewByName(WebspaceSettingAdmin::CONTENT_VIEW);

        /** @var ToolbarAction[] $toolbarActions */
        $toolbarActions = $contentView->getOption('toolbarActions');
        $types = \array_map(static fn (ToolbarAction $toolbarAction) => $toolbarAction->getType(), $toolbarActions);

        $this->assertNotContains('sulu_admin.delete', $types);
        $this->assertNotContains('sulu_admin.type', $types);
        $this->assertContains('sulu_admin.dropdown', $types);
        $this->assertContains('sulu_content.review_workflow_transition_request', $types);
    }

    public function testEditDropdownKeepsTheLiveConditionsAndHasTextsForSettings(): void
    {
        $contentView = $this->getViewRegistry()->findViewByName(WebspaceSettingAdmin::CONTENT_VIEW);

        /** @var ToolbarAction[] $toolbarActions */
        $toolbarActions = $contentView->getOption('toolbarActions');
        $editAction = \array_values(\array_filter(
            $toolbarActions,
            static fn (ToolbarAction $toolbarAction) => 'sulu_admin.dropdown' === $toolbarAction->getType()
                && 'sulu_admin.edit' === $toolbarAction->getOptions()['label'],
        ))[0];

        /** @var ToolbarAction[] $editActions */
        $editActions = $editAction->getOptions()['toolbarActions'];
        $editOptions = [];
        foreach ($editActions as $toolbarAction) {
            $editOptions[$toolbarAction->getType()] = $toolbarAction->getOptions();
        }

        $this->assertSame(['sulu_admin.copy_locale', 'sulu_admin.delete_draft', 'sulu_admin.set_unpublished'], \array_keys($editOptions));
        $this->assertSame('(!_permissions || _permissions.live)', $editOptions['sulu_admin.delete_draft']['visible_condition']);
        $this->assertSame('sulu_page.webspace_setting_delete_draft_warning_text', $editOptions['sulu_admin.delete_draft']['warning_text']);
        $this->assertSame('(!_permissions || _permissions.live)', $editOptions['sulu_admin.set_unpublished']['visible_condition']);
        $this->assertSame('sulu_page.webspace_setting_unpublish_warning_text', $editOptions['sulu_admin.set_unpublished']['warning_text']);
    }

    public function testFormMetadataOfTheWebspaceHasOnlyTheFieldsOfTheWebspaceSettingsForm(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/metadata/form/webspace_settings?locale=en&webspace=sulu-io');

        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{defaultType: string, types: array<string, array{form: array<string, mixed>}>} $metadata */
        $metadata = \json_decode((string) $response->getContent(), true);

        $this->assertSame(['webspace_settings_sulu_io'], \array_keys($metadata['types']));
        $this->assertSame('webspace_settings_sulu_io', $metadata['defaultType']);
        $this->assertSame(
            ['companyName', 'email', 'privacyPage', 'copyright'],
            \array_keys($metadata['types']['webspace_settings_sulu_io']['form']),
        );
    }

    private function getViewRegistry(): ViewRegistry
    {
        return self::getContainer()->get('sulu_admin.view_registry');
    }
}
