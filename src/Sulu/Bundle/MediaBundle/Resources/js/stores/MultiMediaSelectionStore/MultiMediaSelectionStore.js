// @flow
import {action, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {arrayMove} from 'sulu-admin-bundle/components';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {Media} from '../../types';

const MEDIA_RESOURCE_KEY = 'media';

export default class MultiMediaSelectionStore {
    @observable selectedMedia: Array<Media> = [];
    @observable loading: boolean = false;

    constructor(selectedMediaIds: ?Array<number>, locale: IObservableValue<string>) {
        if (selectedMediaIds && selectedMediaIds.length) {
            this.loadSelectedMedia(selectedMediaIds, locale);
        }
    }

    @action add(media: Media) {
        this.selectedMedia.push(media);
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
