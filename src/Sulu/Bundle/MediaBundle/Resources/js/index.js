// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {viewRegistry} from 'sulu-admin-bundle/containers';
import MediaOverview from './views/MediaOverview';

viewRegistry.add('sulu_media.overview', MediaOverview);

bundleReady();
