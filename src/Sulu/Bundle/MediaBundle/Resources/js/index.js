// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {viewStore} from 'sulu-admin-bundle/containers';
import MediaList from './views/MediaList';

viewStore.add('sulu_media.collections', MediaList);

bundleReady();
