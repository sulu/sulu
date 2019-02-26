// @flow
import type {IObservableValue} from 'mobx';
import {action, computed, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {Media} from '../../types';

const MEDIA_RESOURCE_KEY = 'media';

export default class SingleMediaSelectionStore {
    @observable selectedMedia: ?Media;
    @observable loading: boolean = false;

    constructor(selectedMediaId: ?number, locale: IObservableValue<string>) {
        if (selectedMediaId) {
            this.loadSelectedMedia(selectedMediaId, locale);
        }
    }

    @action set(media: Media) {
        this.selectedMedia = media;
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

    loadSelectedMedia = (selectedMediaId: ?number, locale: IObservableValue<string>) => {
        if (!selectedMediaId) {
            this.clear();
            return;
        }

        this.setLoading(true);
        return ResourceRequester.get(MEDIA_RESOURCE_KEY, {
            id: selectedMediaId,
            locale: locale.get(),
        }).then((data) => {
            this.set(data);
            this.setLoading(false);
        });
    };
}
