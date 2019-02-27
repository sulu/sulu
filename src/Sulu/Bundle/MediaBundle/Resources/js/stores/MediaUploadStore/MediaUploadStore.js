// @flow
import {action, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {ResourceRequester, resourceRouteRegistry} from 'sulu-admin-bundle/services';
import type {Media} from '../../types';

const RESOURCE_KEY = 'media';

export default class MediaUploadStore {
    @observable uploading: boolean;
    @observable progress: number;
    @observable media: ?Media;
    locale: IObservableValue<string>;

    constructor(media: ?Media, locale: IObservableValue<string>) {
        this.media = media;
        this.locale = locale;
    }

    @computed get id(): ?number | string {
        const {media} = this;

        if (!media) {
            return;
        }

        return media.id;
    }

    @computed get downloadUrl(): ?string {
        const {media} = this;

        if (!media) {
            return;
        }

        return media.url;
    }

    getThumbnail(size: string): ?string {
        const {media} = this;

        if (!media) {
            return;
        }

        const {
            thumbnails,
        } = media;

        if (!thumbnails || !thumbnails[size]) {
            return;
        }

        return thumbnails[size];
    }

    @computed get mimeType(): ?string {
        const {media} = this;

        if (!media) {
            return;
        }

        return media.mimeType;
    }

    @action setUploading(uploading: boolean) {
        this.uploading = uploading;
    }

    @action setProgress(progress: number) {
        this.progress = Math.ceil(progress);
    }

    @action delete() {
        if (!this.id) {
            throw new Error('The "id" property must be available for deleting a media');
        }

        return ResourceRequester.delete(RESOURCE_KEY, {id: this.id})
            .then(action(() => {
                this.media = undefined;
            }));
    }

    update(file: File): Promise<*> {
        const id = this.media ? this.media.id : undefined;

        if (!id) {
            throw new Error('The "id" property must be available for updating a media');
        }

        const url = resourceRouteRegistry.getDetailUrl(
            RESOURCE_KEY,
            {
                action: 'new-version',
                id,
                locale: this.locale.get(),
            }
        );

        this.setUploading(true);

        return this.upload(file, url)
            .then(this.handleResponse);
    }

    create(collectionId: string | number, file: File): Promise<*> {
        const url = resourceRouteRegistry.getDetailUrl(
            RESOURCE_KEY,
            {
                collection: collectionId,
                locale: this.locale.get(),
            }
        );

        this.setUploading(true);

        return this.upload(file, url)
            .then(this.handleResponse);
    }

    @action handleResponse = (media: Object) => {
        this.setUploading(false);
        this.setProgress(0);
        this.media = media;

        return media;
    };

    upload(file: File, url: string): Promise<*> {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const form = new FormData();

            xhr.open('POST', url);

            xhr.onload = (event: any) => resolve(JSON.parse(event.target.response));
            xhr.onerror = (event: any) => reject(event.target.response);

            if (xhr.upload) {
                xhr.upload.onprogress = (event) => this.setProgress(event.loaded / event.total * 100);
            }

            form.append('fileVersion', file);
            xhr.send(form);
        });
    }
}
