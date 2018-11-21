// @flow
import type {IObservableValue} from 'mobx';
import {action, computed, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {MediaItem} from '../MultiMediaSelectionStore/types';

const THUMBNAIL_SIZE = 'sulu-25x25';
const MEDIA_RESOURCE_KEY = 'media';

export default class SingleMediaSelectionStore {
    @observable selectedMedia: ?MediaItem;
    @observable loading: boolean = false;

    constructor(selectedMediaId: ?number, locale: IObservableValue<string>) {
        if (selectedMediaId) {
            this.loadSelectedMedia(selectedMediaId, locale);
        }
    }

    @action set(media: ?Object) {
        this.selectedMedia = media ? this.prepareMedia(media) : undefined;
    }

    @action clear() {
        this.selectedMedia = undefined;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @computed get selectedMediaId(): ?number {
        return this.selectedMedia ? this.selectedMedia.id : undefined;
    }

    prepareMedia(media: Object) {
        return {
            id: media.id,
            title: media.title,
            mimeType: media.mimeType,
            thumbnail: media.thumbnails ? media.thumbnails[THUMBNAIL_SIZE] : null,
        };
    }

    loadSelectedMedia = (selectedMediaId: ?number, locale: IObservableValue<string>) => {
        if (!selectedMediaId) {
            this.set(undefined);
            return;
        }

        this.setLoading(true);
        return ResourceRequester.get(MEDIA_RESOURCE_KEY, selectedMediaId, {
            locale: locale.get(),
        }).then((data) => {
            this.set(data);
            this.setLoading(false);
        });
    };
}
