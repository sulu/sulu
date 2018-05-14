// @flow
import {action, computed, observable} from 'mobx';
import {ResourceMetadataStore, ResourceStore} from 'sulu-admin-bundle/stores';
import {ResourceRequester} from 'sulu-admin-bundle/services';

const RESOURCE_KEY = 'media';

export default class MediaUploadStore {
    @observable uploading: boolean;
    @observable progress: number;
    resourceStore: ResourceStore;

    constructor(resourceStore: ResourceStore) {
        if (resourceStore.resourceKey !== RESOURCE_KEY) {
            throw new Error('The MediaUploadStore needs a "ResourceStore" with the "media" resourceKey!');
        }
        this.resourceStore = resourceStore;
    }

    @computed get id(): ?number | string {
        const {resourceStore} = this;

        return resourceStore.id;
    }

    @computed get locale(): string {
        const {resourceStore} = this;

        if (!resourceStore.locale) {
            throw new Error('The MediaUploadStore needs a localized "ResourceStore"!');
        }

        return resourceStore.locale.get();
    }

    getThumbnail(size: string): ?string {
        const {resourceStore} = this;
        const {
            data: {
                thumbnails,
            },
        } = resourceStore;

        if (!thumbnails || !thumbnails[size]) {
            return;
        }

        return thumbnails[size];
    }

    @computed get mimeType(): string {
        const {resourceStore} = this;

        return resourceStore.data.mimeType;
    }

    @action setUploading(uploading: boolean) {
        this.uploading = uploading;
    }

    @action setProgress(progress: number) {
        this.progress = Math.ceil(progress);
    }

    update(file: File): Promise<*> {
        const {id} = this.resourceStore;

        if (!id) {
            throw new Error('The "id" property must be available for updating a media');
        }

        const endpoint = ResourceMetadataStore.getEndpoint(RESOURCE_KEY);
        const queryString = ResourceRequester.buildQueryString({
            action: 'new-version',
            locale: this.locale,
        });
        const url = endpoint + '/' + id + queryString;

        this.setUploading(true);

        return this.upload(file, url)
            .then(this.handleResponse);
    }

    create(collectionId: string | number, file: File): Promise<*> {
        const endpoint = ResourceMetadataStore.getEndpoint(RESOURCE_KEY);
        const queryString = ResourceRequester.buildQueryString({
            locale: this.locale,
            collection: collectionId,
        });
        const url = endpoint + queryString;

        this.setUploading(true);

        return this.upload(file, url)
            .then(this.handleResponse);
    }

    @action handleResponse = (media: Object) => {
        this.setUploading(false);
        this.setProgress(0);
        this.resourceStore.setMultiple(media);

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
