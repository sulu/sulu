// @flow
import {action, autorun, observable} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import metadataStore from './MetadataStore';

export default class DatagridStore {
    page: observable = observable();
    locale: observable = observable();
    @observable pageCount: number = 0;
    @observable data: Array<Object> = [];
    @observable selections: Array<string | number> = [];
    @observable loading: boolean = true;
    disposer: () => void;
    resourceKey: string;
    options: Object;

    constructor(resourceKey: string, options: Object = {}) {
        this.resourceKey = resourceKey;
        this.options = options;
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
        const defaultOptions = {};
        defaultOptions.page = page;

        const locale = this.locale.get();
        if (locale) {
            defaultOptions.locale = locale;
        }

        ResourceRequester.getList(this.resourceKey, {
            ...defaultOptions,
            ...this.options,
        }).then(this.handleResponse);
    };

    @action handleResponse = (response: Object) => {
        this.data = response._embedded[this.resourceKey];
        this.pageCount = response.pages;
        this.setLoading(false);
    };

    @action setLoading(loading: boolean) {
        this.loading = loading;
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

    @action setLocale(locale: string) {
        this.locale.set(locale);
    }

    @action select(id: string | number) {
        if (this.selections.includes(id)) {
            return;
        }

        this.selections.push(id);
    }

    @action selectEntirePage() {
        this.data.forEach((item) => {
            this.select(item.id);
        });
    }

    @action deselect(id: string | number) {
        const index = this.selections.indexOf(id);
        if (index === -1) {
            return;
        }

        this.selections.splice(index, 1);
    }

    @action deselectEntirePage() {
        this.data.forEach((item) => {
            this.deselect(item.id);
        });
    }

    @action clearSelection() {
        this.selections = [];
    }

    destroy() {
        this.disposer();
    }
}
