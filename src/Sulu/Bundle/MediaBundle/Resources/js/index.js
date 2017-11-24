// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {datagridAdapterRegistry, viewRegistry} from 'sulu-admin-bundle/containers';
import {MediaCardOverviewAdapter} from './containers/Datagrid';
import MediaOverview from './views/MediaOverview';

viewRegistry.add('sulu_media.overview', MediaOverview);
datagridAdapterRegistry.add('media_card_overview', MediaCardOverviewAdapter);

bundleReady();
