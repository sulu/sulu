// @flow
import {action, autorun, observable} from 'mobx';
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
    observableOptions: Object;

    constructor(resourceKey: string, observableOptions: Object = {}, options: Object = {}) {
        this.resourceKey = resourceKey;
        this.observableOptions = observableOptions;

        if (!this.observableOptions.page) {
            throw new Error('The DatagridStore expects an observable "page" property inside the "observableOptions".');
        }

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

        if (this.observableOptions.locale) {
            defaultOptions.locale = this.observableOptions.locale.get();
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
        const page = parseInt(this.observableOptions.page.get());
        if (!page) {
            return undefined;
        }

        return page;
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
