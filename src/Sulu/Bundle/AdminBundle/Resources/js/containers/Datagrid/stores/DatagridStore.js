// @flow
import {action, autorun, observable} from 'mobx';
import Requester from '../../../services/Requester';
import metadataStore from './MetadataStore';

export default class DatagridStore {
    page: observable = observable();
    @observable pageCount: number = 0;
    @observable data: Array<Object> = [];
    @observable isLoading: boolean = true;
    disposer: () => void;
    baseUrl: string;
    resourceKey: string;

    constructor(resourceKey: string, baseUrl: string) {
        this.resourceKey = resourceKey;
        this.baseUrl = baseUrl;
        this.disposer = autorun(this.sendRequest);
    }

    getFields() {
        return metadataStore.getFields(this.resourceKey);
    }

    sendRequest = () => {
        const page = this.getPage();
        if (!page) {
            return;
        }

        this.setLoading(true);
        Requester.get(this.baseUrl + '?flat=true&page=' + page + '&limit=10')
            .then(this.handleResponse);
    };

    @action handleResponse = (response: Object) => {
        this.data = response._embedded[this.resourceKey];
        this.pageCount = response.pages;
        this.setLoading(false);
    };

    @action setLoading(isLoading: boolean) {
        this.isLoading = isLoading;
    }

    getPage(): ?number {
        const page = parseInt(this.page.get());
        if (!page) {
            return undefined;
        }

        return page;
    }

    @action setPage(page: number) {
        if (this.page.get() == page) {
            return;
        }

        this.page.set(page);
    }

    destroy() {
        this.disposer();
    }
}
