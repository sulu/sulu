// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {viewRegistry} from 'sulu-admin-bundle/containers';
import WebspaceOverview from './views/WebspaceOverview';
import PageForm from './views/PageForm';

viewRegistry.add('sulu_content.webspace_overview', WebspaceOverview);
viewRegistry.add('sulu_content.page_form', PageForm);

bundleReady();
