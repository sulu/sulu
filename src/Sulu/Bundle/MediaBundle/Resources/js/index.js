// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {datagridAdapterRegistry, fieldRegistry, viewRegistry} from 'sulu-admin-bundle/containers';
import {MediaCardOverviewAdapter, MediaCardSelectionAdapter} from './containers/Datagrid';
import {SingleMediaUpload} from './containers/Form';
import MediaOverview from './views/MediaOverview';
import MediaDetail from './views/MediaDetail';
import MediaSelection from './containers/MediaSelection';

viewRegistry.add('sulu_media.overview', MediaOverview);
viewRegistry.add('sulu_media.detail', MediaDetail);

datagridAdapterRegistry.add('media_card_overview', MediaCardOverviewAdapter);
datagridAdapterRegistry.add('media_card_selection', MediaCardSelectionAdapter);

fieldRegistry.add('media_selection', MediaSelection);
fieldRegistry.add('single_media_upload', SingleMediaUpload);

bundleReady();
