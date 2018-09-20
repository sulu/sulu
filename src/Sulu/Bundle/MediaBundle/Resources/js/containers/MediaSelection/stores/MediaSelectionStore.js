// @flow
import {action, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line
import {arrayMove} from 'sulu-admin-bundle/components';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {MediaItem} from '../types';

const THUMBNAIL_SIZE = 'sulu-25x25';
const MEDIA_RESOURCE_KEY = 'media';

export default class MediaSelectionStore {
    @observable selectedMedia: Array<MediaItem> = [];
    @observable loading: boolean = false;

    constructor(selectedMediaIds: ?Array<number>, locale: IObservableValue<string>) {
        if (selectedMediaIds && selectedMediaIds.length) {
            this.loadSelectedMedia(selectedMediaIds, locale);
        }
    }

    @action add(media: Object) {
        const preparedMedia = this.prepareMedia(media);
        this.selectedMedia.push(preparedMedia);
    }

    @action removeById(mediaId: number) {
        this.selectedMedia = this.selectedMedia.filter((media) => media.id !== mediaId);
    }

    @action move(oldItemIndex: number, newItemIndex: number) {
        this.selectedMedia = arrayMove(this.selectedMedia, oldItemIndex, newItemIndex);
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @computed get selectedMediaIds(): Array<number> {
        return this.selectedMedia.map((media) => media.id);
    }

    prepareMedia(media: Object) {
        return {
            id: media.id,
            title: media.title,
            mimeType: media.mimeType,
            thumbnail: media.thumbnails ? media.thumbnails[THUMBNAIL_SIZE] : null,
        };
    }

    loadSelectedMedia = (selectedMediaIds: Array<number>, locale: IObservableValue<string>) => {
        this.setLoading(true);
        return ResourceRequester.getList(MEDIA_RESOURCE_KEY, {
            locale: locale.get(),
            ids: selectedMediaIds.join(','),
            limit: undefined, // TODO: Should be replaced by pagination
            page: 1,
        }).then((data) => {
            const {
                _embedded: {
                    media,
                },
            } = data;

            media.forEach((mediaItem) => {
                this.add(mediaItem);
            });
            this.setLoading(false);
        });
    };
}
