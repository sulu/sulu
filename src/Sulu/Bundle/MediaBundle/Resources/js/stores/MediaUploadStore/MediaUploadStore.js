// @flow
import {action, computed, observable} from 'mobx';
import {ResourceStore, ResourceMetadataStore} from 'sulu-admin-bundle/stores';
import {ResourceRequester} from 'sulu-admin-bundle/services';

const RESOURCE_KEY = 'media';
const THUMBNAIL_SIZE = 'sulu-400x400-inset';

export default class MediaUploadStore {
    resourceStore: ResourceStore;
    @observable uploading: boolean;
    @observable progress: number;

    constructor(resourceStore: ResourceStore) {
        this.resourceStore = resourceStore;
    }

    @computed get source(): ?string {
        const {
            data: {
                thumbnails,
            },
        } = this.resourceStore;

        if (!thumbnails || !thumbnails[THUMBNAIL_SIZE]) {
            return null;
        }

        return `${window.location.origin}${thumbnails[THUMBNAIL_SIZE]}`;
    }

    @computed get mimeType(): string {
        return this.resourceStore.data.mimeType;
    }

    @action setUploading(uploading: boolean) {
        this.uploading = uploading;
    }

    @action setProgress(progress: number) {
        this.progress = Math.ceil(progress);
    }

    update(mediaId: string | number, file: File) {
        const baseUrl = ResourceMetadataStore.getBaseUrl(RESOURCE_KEY);
        const queryString = ResourceRequester.buildQueryString({
            action: 'new-version',
            locale: this.resourceStore.locale ? this.resourceStore.locale.get() : undefined,
        });
        const url = baseUrl + '/' + mediaId + queryString;

        this.setUploading(true);

        this.upload(file, url)
            .then((data: Object) => {
                for (const key of Object.keys(data)) {
                    this.resourceStore.set(key, data[key]);
                }
                this.setUploading(false);
                this.setProgress(0);
            });
    }

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
