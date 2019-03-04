// @flow
import {action, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {MediaFormat, MediaFormats} from './types';

const RESOURCE_KEY = 'media_formats';

export default class MediaFormatStore
{
    id: string | number;
    locale: string;
    @observable mediaFormats: MediaFormats;
    @observable loading: boolean;
    @observable saving: boolean;

    constructor(id: number | string, locale: string) {
        this.id = id;
        this.locale = locale;
        this.loading = true;
        ResourceRequester.getList(RESOURCE_KEY, {id, locale}).then(action((response) => {
            this.loading = false;
            this.mediaFormats = response;
        }));
    }

    getFormatOptions(formatKey: string): ?MediaFormat {
        return this.mediaFormats[formatKey];
    }

    @action updateFormatOptions(formatKey: string, options: MediaFormat) {
        this.saving = true;

        return ResourceRequester
            .put(RESOURCE_KEY, options, {id: this.id, key: formatKey, locale: this.locale})
            .then(action((response) => {
                this.saving = false;
                this.mediaFormats[formatKey] = response;
            }));
    }
}
