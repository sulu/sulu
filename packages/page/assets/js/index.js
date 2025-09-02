// @flow
import {initializer, Config} from 'sulu-admin-bundle/services';
import {
    conditionDataProviderRegistry,
    fieldRegistry,
    viewRegistry,
    ResourceLocator,
} from 'sulu-admin-bundle/containers';
import webspaceConditionDataProvider from './containers/Form/conditionDataProviders/webspaceConditionDataProvider';
import SearchResult from './containers/Form/fields/SearchResult';
import SegmentSelect from './containers/Form/fields/SegmentSelect';
import TeaserSelection from './containers/Form/fields/TeaserSelection';
import {teaserProviderRegistry} from './containers/TeaserSelection';
import PageSettingsNavigationSelect from './containers/Form/fields/PageSettingsNavigationSelect';
import PageSettingsShadowLocaleSelect from './containers/Form/fields/PageSettingsShadowLocaleSelect';
import SettingsVersions from './containers/Form/fields/SettingsVersions';
import webspaceStore from './stores/webspaceStore';
import {loadResourceLocatorInputTypeByWebspace} from './utils/Webspace';
import PageTabs from './views/PageTabs';
import PageList from './views/PageList';
import WebspaceTabs from './views/WebspaceTabs';
import PageTreeRoute from './containers/Form/fields/PageTreeRoute';

initializer.addUpdateConfigHook('sulu_page', (config: Object, initialized: boolean) => {
    // $FlowFixMe
    webspaceStore.setWebspaces(Object.values(config.webspaces));

    if (initialized) {
        return;
    }

    viewRegistry.add('sulu_page.page_tabs', PageTabs, {disableDefaultSpacing: true});
    viewRegistry.add('sulu_page.page_list', PageList);
    viewRegistry.add('sulu_page.webspace_tabs', WebspaceTabs, {disableDefaultSpacing: true});

    fieldRegistry.add('page_settings_navigation_select', PageSettingsNavigationSelect);
    fieldRegistry.add('page_settings_shadow_locale_select', PageSettingsShadowLocaleSelect);
    fieldRegistry.add('search_result', SearchResult);
    fieldRegistry.add('segment_select', SegmentSelect);
    fieldRegistry.add('teaser_selection', TeaserSelection);

    conditionDataProviderRegistry.add(webspaceConditionDataProvider);

    fieldRegistry.add(
        'resource_locator',
        ResourceLocator,
        {
            modeResolver: (props) => loadResourceLocatorInputTypeByWebspace(props.formInspector.options.webspace),
            generationUrl: Config.endpoints.generateUrl,
            historyResourceKey: 'page_resourcelocators',
            resourceStorePropertiesToRequest: {
                parentUuid: 'parentId',
            },
        }
    );

    fieldRegistry.add(
        'page_tree_route',
        PageTreeRoute,
        {
            modeResolver: () => {
                return Promise.resolve('leaf');
            },
        }
    );

    if (config.versioning) {
        fieldRegistry.add('settings_versions', SettingsVersions);
    }

    for (const teaserProviderKey in config.teaser) {
        teaserProviderRegistry.add(teaserProviderKey, config.teaser[teaserProviderKey]);
    }
});
