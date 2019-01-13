// @flow
import {action, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {ResourceMetadataStore} from 'sulu-admin-bundle/stores';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {buildQueryString} from 'sulu-admin-bundle/utils';
import type {Media} from '../../types';
import {translate} from 'sulu-admin-bundle/utils';

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

        return ResourceRequester.delete(RESOURCE_KEY, this.id)
            .then(action(() => {
                this.media = undefined;
            }));
    }

    update(file: File): Promise<*> {
        const id = this.media ? this.media.id : undefined;

        if (!id) {
            throw new Error('The "id" property must be available for updating a media');
        }

        const endpoint = ResourceMetadataStore.getEndpoint(RESOURCE_KEY);
        const queryString = buildQueryString({
            action: 'new-version',
            locale: this.locale.get(),
        });
        const url = endpoint + '/' + id + queryString;

        this.setUploading(true);

        return this.upload(file, url)
            .then(this.handleResponse)
            .catch(this.handleErrorResponse);
    }

    create(collectionId: string | number, file: File): Promise<*> {
        const endpoint = ResourceMetadataStore.getEndpoint(RESOURCE_KEY);
        const queryString = buildQueryString({
            locale: this.locale.get(),
            collection: collectionId,
        });
        const url = endpoint + queryString;

        this.setUploading(true);

        return this.upload(file, url)
            .then(this.handleResponse)
            .catch(this.handleErrorResponse);
    }

    @action handleResponse = (media: Object) => {
        this.setUploading(false);
        this.setProgress(0);
        this.media = media;

        return media;
    };

    /**
     * Handle upload errors
     * 
     * Trigger an error message and reset upload indicators
     * 
     * @param  error Exception from XHR promise
     * @throws Error Including translated error message based on status code
     */
    @action handleErrorResponse = (error: any) => {
        this.setUploading(false);
        this.setProgress(0);

        let statusCode = error.status;
        throw new Error(translate('sulu_media.error_' + statusCode));
    };
  
    upload(file: File, url: string): Promise<*> {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const form = new FormData();

            xhr.open('POST', url);

            xhr.onload = (event: any) => {
                let uploadStatus = parseInt(event.target.status);
                if (uploadStatus >= 200 && uploadStatus < 300) {
                    resolve(JSON.parse(event.target.response));
                } else {
                    // reject if HTTP status isn't 2xx  
                    reject({
                        status: uploadStatus,
                        statusText: event.target.response
                    });
                }
            };
          
            xhr.onerror = (event: any) => {
                reject({
                    status: 'general',
                    statusText: 'Unknown error'
                });
            }

            if (xhr.upload) {
                xhr.upload.onprogress = (event) => this.setProgress(event.loaded / event.total * 100);
            }

            form.append('fileVersion', file);
            xhr.send(form);
        });
    }
}
