// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {viewStore} from 'sulu-admin-bundle/containers';
import MediaOverview from './views/MediaOverview';

viewStore.add('sulu_media.overview', MediaOverview);

bundleReady();
