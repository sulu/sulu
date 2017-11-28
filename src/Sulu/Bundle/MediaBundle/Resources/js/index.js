// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {datagridAdapterRegistry, fieldRegistry, viewRegistry} from 'sulu-admin-bundle/containers';
import {MediaCardOverviewAdapter, MediaCardSelectionAdapter} from './containers/Datagrid';
import MediaOverview from './views/MediaOverview';
import MediaSelection from './containers/MediaSelection';

viewRegistry.add('sulu_media.overview', MediaOverview);

datagridAdapterRegistry.add('media_card_overview', MediaCardOverviewAdapter);
datagridAdapterRegistry.add('media_card_selection', MediaCardSelectionAdapter);

fieldRegistry.add('media_selection', MediaSelection);

bundleReady();
