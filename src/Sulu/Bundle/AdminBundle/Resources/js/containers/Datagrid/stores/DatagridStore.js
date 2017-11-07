// @flow
import {action, autorun, intercept, observable} from 'mobx';
import type {ObservableOptions} from '../types';
import ResourceRequester from '../../../services/ResourceRequester';
import metadataStore from './MetadataStore';

export default class DatagridStore {
    @observable pageCount: number = 0;
    @observable data: Array<Object> = [];
    @observable selections: Array<string | number> = [];
    @observable loading: boolean = true;
    disposer: () => void;
    resourceKey: string;
    options: Object;
    observableOptions: ObservableOptions;
    appendRequestData: boolean;
    localeInterceptDisposer: () => void;
    dataObserveDisposer: () => void;

    constructor(
        resourceKey: string,
        observableOptions: ObservableOptions,
        options: Object = {},
        appendRequestData: boolean = false
    ) {
        this.resourceKey = resourceKey;
        this.observableOptions = observableOptions;
        this.options = options;
        this.disposer = autorun(this.sendRequest);
        this.appendRequestData = appendRequestData;

        if (this.appendRequestData) {
            this.localeInterceptDisposer = intercept(this.observableOptions.locale, this.localeInterceptor);
        }
    }

    localeInterceptor = (change: observable) => {
        if (this.observableOptions.locale !== change.newValue) {
            this.data = [];
            return change;
        }
    };

    getFields() {
        return metadataStore.getFields(this.resourceKey);
    }

    sendRequest = () => {
        const page = this.getPage();

        if (!page) {
            return;
        }

        const observableOptions = {};
        observableOptions.page = page;

        if (this.observableOptions.locale) {
            observableOptions.locale = this.observableOptions.locale.get();
        }

        this.setLoading(true);

        ResourceRequester.getList(this.resourceKey, {
            ...observableOptions,
            ...this.options,
        }).then(this.handleResponse);
    };

    @action handleResponse = (response: Object) => {
        const data = response._embedded[this.resourceKey];

        if (this.appendRequestData) {
            this.data = [...this.data, ...data];
        } else {
            this.data = data;
        }

        this.pageCount = response.pages;
        this.setLoading(false);
    };

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    getPage(): ?number {
        const page = parseInt(this.observableOptions.page.get());
        if (!page) {
            return undefined;
        }

        return page;
    }

    @action setPage(page: number) {
        this.observableOptions.page.set(page);
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

        if (this.localeInterceptDisposer) {
            this.localeInterceptDisposer();
        }
    }
}
