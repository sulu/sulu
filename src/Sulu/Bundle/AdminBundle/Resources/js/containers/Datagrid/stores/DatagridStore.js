// @flow
import {action, autorun, intercept, observable, computed} from 'mobx';
import type {IValueWillChange} from 'mobx'; // eslint-disable-line import/named
import type {LoadingStrategyInterface, ObservableOptions} from '../types';
import metadataStore from './MetadataStore';

export default class DatagridStore {
    @observable pageCount: number = 0;
    @observable data: Array<Object> = [];
    @observable selections: Array<string | number> = [];
    @observable loading: boolean = true;
    @observable loadingStrategy: LoadingStrategyInterface;
    disposer: () => void;
    resourceKey: string;
    options: Object;
    observableOptions: ObservableOptions;
    localeInterceptionDisposer: () => void;

    constructor(
        resourceKey: string,
        observableOptions: ObservableOptions,
        options: Object = {}
    ) {
        this.resourceKey = resourceKey;
        this.observableOptions = observableOptions;
        this.options = options;
        this.disposer = autorun(this.sendRequest);
    }

    @computed get initialized(): boolean {
        return !!this.loadingStrategy;
    }

    @action init = (loadingStrategy: LoadingStrategyInterface) => {
        this.updateLoadingStrategy(loadingStrategy);
    };

    @action updateLoadingStrategy = (loadingStrategy: LoadingStrategyInterface) => {
        if (this.loadingStrategy === loadingStrategy) {
            return;
        }

        this.data = [];
        this.pageCount = 0;
        this.setPage(1);
        this.loadingStrategy = loadingStrategy;

        if (this.localeInterceptionDisposer) {
            this.localeInterceptionDisposer();
        }

        if ('InfiniteScrollingStrategy' === this.loadingStrategy.constructor.name && this.observableOptions.locale) {
            this.localeInterceptionDisposer = intercept(this.observableOptions.locale, '', this.handleLocaleChanges);
        }
    };

    handleLocaleChanges = (change: IValueWillChange<number>) => {
        if (this.observableOptions.locale !== change.newValue) {
            this.data = [];
            this.observableOptions.page.set(1);

            return change;
        }
    };

    getSchema() {
        return metadataStore.getSchema(this.resourceKey);
    }

    @action reload() {
        const page = this.getPage();
        this.structureStrategy.clear();

        if (page && page > 1) {
            this.setPage(1);
        } else {
            this.sendRequest();
        }
    }

    sendRequest = () => {
        if (!this.initialized) {
            return;
        }

        const page = this.getPage();

        const observableOptions = {};
        observableOptions.page = page;

        if (this.observableOptions.locale) {
            observableOptions.locale = this.observableOptions.locale.get();
        }

        this.setLoading(true);

        this.loadingStrategy.load(this.data, this.resourceKey, {...observableOptions, ...this.options})
            .then(action((response) => {
                this.handleResponse(response);
            }));
    };

    // TODO remove
    handleResponse = (response: Object) => {
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

        if (this.localeInterceptionDisposer) {
            this.localeInterceptionDisposer();
        }
    }
}
