// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {datagridAdapterRegistry, viewRegistry} from 'sulu-admin-bundle/containers';
import MasonryAdapter from './containers/MasonryAdapter';
import MediaOverview from './views/MediaOverview';

viewRegistry.add('sulu_media.overview', MediaOverview);
datagridAdapterRegistry.add('masonry', MasonryAdapter);

bundleReady();
