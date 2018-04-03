// @flow
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {ResourceMetadataStore} from 'sulu-admin-bundle/stores';
import {ResourceRequester} from 'sulu-admin-bundle/services';

const RESOURCE_KEY = 'media';

export default class MediaUploadStore {
    locale: IObservableValue<string>;
    @observable uploading: boolean;
    @observable progress: number;
    @observable data: Object = {};

    constructor(locale: IObservableValue<string>) {
        this.locale = locale;
    }

    @action setUploading(uploading: boolean) {
        this.uploading = uploading;
    }

    @action setProgress(progress: number) {
        this.progress = Math.ceil(progress);
    }

    @action setData(data: Object) {
        this.data = data;
    }

    update(mediaId: string | number, file: File): Promise<*> {
        const endpoint = ResourceMetadataStore.getEndpoint(RESOURCE_KEY);
        const queryString = ResourceRequester.buildQueryString({
            action: 'new-version',
            locale: this.locale.get(),
        });
        const url = endpoint + '/' + mediaId + queryString;

        this.setUploading(true);

        return this.upload(file, url)
            .then(this.handleResponse);
    }

    create(collectionId: string | number, file: File): Promise<*> {
        const endpoint = ResourceMetadataStore.getEndpoint(RESOURCE_KEY);
        const queryString = ResourceRequester.buildQueryString({
            locale: this.locale.get(),
            collection: collectionId,
        });
        const url = endpoint + queryString;

        this.setUploading(true);

        return this.upload(file, url)
            .then(this.handleResponse);
    }

    handleResponse = (data: Object) => {
        this.setUploading(false);
        this.setProgress(0);
        this.setData(data);

        return data;
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
