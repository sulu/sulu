// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {fieldRegistry, viewRegistry} from 'sulu-admin-bundle/containers';
import SearchResult from './containers/Form/fields/SearchResult';
import WebspaceOverview from './views/WebspaceOverview';
import PageTabs from './views/PageTabs';

viewRegistry.add('sulu_content.page_tabs', PageTabs);
viewRegistry.add('sulu_content.webspace_overview', WebspaceOverview);

fieldRegistry.add('search_result', SearchResult);

bundleReady();
