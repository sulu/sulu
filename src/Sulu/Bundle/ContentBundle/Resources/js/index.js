// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {fieldRegistry, viewRegistry} from 'sulu-admin-bundle/containers';
import {toolbarActionRegistry} from 'sulu-admin-bundle/views';
import SearchResult from './containers/Form/fields/SearchResult';
import WebspaceNavigationSelect from './containers/Form/fields/WebspaceNavigationSelect';
import EditToolbarAction from './views/Form/toolbarActions/EditToolbarAction';
import PageSettings from './views/PageSettings';
import PageTabs from './views/PageTabs';
import WebspaceOverview from './views/WebspaceOverview';

viewRegistry.add('sulu_content.page_settings', PageSettings);
viewRegistry.add('sulu_content.page_tabs', PageTabs);
viewRegistry.add('sulu_content.webspace_overview', WebspaceOverview);

fieldRegistry.add('search_result', SearchResult);
fieldRegistry.add('webspace_navigation_select', WebspaceNavigationSelect);

toolbarActionRegistry.add('sulu_content.edit', EditToolbarAction);

bundleReady();
