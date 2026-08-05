// @flow
import {initializer} from 'sulu-admin-bundle/services';
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import CacheClearToolbarAction from './containers/CacheClearToolbarAction';
import AnalyticsDomainSelect from './containers/Form/fields/AnalyticsDomainSelect';

initializer.addUpdateConfigHook('sulu_website', (config: Object) => {
    CacheClearToolbarAction.clearCacheEndpoint = config.endpoints.clearCache;
    CacheClearToolbarAction.webspaceCachePermissions = config.webspaceCachePermissions;
});

fieldRegistry.add('analytics_domain_select', AnalyticsDomainSelect);
