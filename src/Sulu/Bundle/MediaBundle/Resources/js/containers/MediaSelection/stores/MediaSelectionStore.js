// @flow
import {action, computed, observable} from 'mobx';
import {arrayMove} from 'sulu-admin-bundle/components';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {MediaItem} from '../types';

const THUMBNAIL_SIZE = 'sulu-25x25';
const MEDIA_RESOURCE_KEY = 'media';

export default class MediaelectionStore {
    @observable selectedMedia: Array<MediaItem> = [];

    constructor(selectedMediaIds: Array<string | number>, locale: string) {
        if (selectedMediaIds && selectedMediaIds.length) {
            this.loadSelectedMedia(selectedMediaIds, locale);
        }
    }

    @action add(media: Object) {
        const preparedMedia = this.prepareMedia(media);
        this.selectedMedia.push(preparedMedia);
    }

    @action removeById(mediaId: string | number) {
        this.selectedMedia = this.selectedMedia.filter((media) => media.id !== mediaId);
    }

    @action move(oldItemIndex: number, newItemIndex: number) {
        this.selectedMedia = arrayMove(this.selectedMedia, oldItemIndex, newItemIndex);
    }

    @computed get selectedMediaIds(): Array<string | number> {
        return this.selectedMedia.map((media) => media.id);
    }

    prepareMedia(media: Object) {
        return {
            id: media.id,
            title: media.title,
            thumbnail: media.thumbnails[THUMBNAIL_SIZE],
        };
    }

    loadSelectedMedia = (selectedMediaIds: Array<string | number>, locale: string) => {
        return ResourceRequester.getList(MEDIA_RESOURCE_KEY, {
            locale,
            ids: selectedMediaIds.join(','),
        }).then((data) => {
            const {
                _embedded: {
                    media,
                },
            } = data;

            media.forEach((mediaItem) => {
                this.add(mediaItem);
            });
        });
    };
}
