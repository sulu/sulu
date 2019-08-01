// @flow
import {initializer} from 'sulu-admin-bundle/services';
import {fieldRegistry, viewRegistry} from 'sulu-admin-bundle/containers';
import {formToolbarActionRegistry} from 'sulu-admin-bundle/views';
import SearchResult from './containers/Form/fields/SearchResult';
import TeaserSelection from './containers/Form/fields/TeaserSelection';
import {teaserProviderRegistry} from './containers/TeaserSelection';
import PageSettingsNavigationSelect from './containers/Form/fields/PageSettingsNavigationSelect';
import PageSettingsShadowLocaleSelect from './containers/Form/fields/PageSettingsShadowLocaleSelect';
import PageSettingsVersions from './containers/Form/fields/PageSettingsVersions';
import EditToolbarAction from './views/Form/toolbarActions/EditToolbarAction';
import TemplateToolbarAction from './views/Form/toolbarActions/TemplateToolbarAction';
import PageTabs from './views/PageTabs';
import PageList from './views/PageList';
import WebspaceTabs from './views/WebspaceTabs';

initializer.addUpdateConfigHook('sulu_page', (config: Object, initialized: boolean) => {
    if (initialized) {
        return;
    }

    viewRegistry.add('sulu_page.page_tabs', PageTabs);
    viewRegistry.add('sulu_page.webspace_overview', PageList);
    viewRegistry.add('sulu_page.webspace_tabs', WebspaceTabs);

    fieldRegistry.add('page_settings_navigation_select', PageSettingsNavigationSelect);
    fieldRegistry.add('page_settings_shadow_locale_select', PageSettingsShadowLocaleSelect);
    fieldRegistry.add('search_result', SearchResult);
    fieldRegistry.add('teaser_selection', TeaserSelection);

    if (config.versioning) {
        fieldRegistry.add('page_settings_versions', PageSettingsVersions);
    }

    formToolbarActionRegistry.add('sulu_page.edit', EditToolbarAction);
    formToolbarActionRegistry.add('sulu_page.templates', TemplateToolbarAction);

    for (const teaserProviderKey in config.teaser) {
        teaserProviderRegistry.add(teaserProviderKey, config.teaser[teaserProviderKey]);
    }
});
