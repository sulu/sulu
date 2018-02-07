// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {viewRegistry} from 'sulu-admin-bundle/containers';
import WebspaceOverview from './views/WebspaceOverview';

viewRegistry.add('sulu_content.webspace_overview', WebspaceOverview);

bundleReady();
