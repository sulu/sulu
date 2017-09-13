// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import MediaList from './views/MediaList';
import MediaCard from './components/MediaCard';

viewStore.add('sulu_media.media_list', MediaList);

bundleReady();

export {
    MediaCard,
};
