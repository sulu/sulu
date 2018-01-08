// @flow
import {action, autorun, observable, computed} from 'mobx';
import type {LoadingStrategyInterface, ObservableOptions, StructureStrategyInterface} from '../types';
import metadataStore from './MetadataStore';

export default class DatagridStore {
    @observable pageCount: number = 0;
    @observable active: ?string | number = undefined;
    @observable selections: Array<string | number> = [];
    @observable loading: boolean = true;
    @observable loadingStrategy: LoadingStrategyInterface;
    @observable structureStrategy: StructureStrategyInterface;
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
        return !!this.loadingStrategy && !!this.structureStrategy;
    }

    @computed get data(): Array<*> {
        return this.structureStrategy.data;
    }

    @action init = (loadingStrategy: LoadingStrategyInterface, structureStrategy: StructureStrategyInterface) => {
        this.updateLoadingStrategy(loadingStrategy);
        this.updateStructureStrategy(structureStrategy);
    };

    @action updateLoadingStrategy = (loadingStrategy: LoadingStrategyInterface) => {
        // do not update if the loading strategy was already defined and it tries to use the same one again
        if (this.loadingStrategy && this.loadingStrategy === loadingStrategy) {
            return;
        }

        if (this.loadingStrategy) {
            this.loadingStrategy.destroy();
        }

        if (this.structureStrategy) {
            this.structureStrategy.clear();
        }

        if (loadingStrategy) {
            loadingStrategy.initialize(this);
        }

        this.loadingStrategy = loadingStrategy;
    };

    @action updateStructureStrategy = (structureStrategy: StructureStrategyInterface) => {
        if (this.structureStrategy === structureStrategy) {
            return;
        }

        this.structureStrategy = structureStrategy;
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

        const data = this.structureStrategy.getData(this.active);
        if (!data) {
            throw new Error('The active item does not exist in the Datagrid');
        }

        const options = {...observableOptions, ...this.options};
        if (this.active) {
            options.parent = this.active;
        }

        this.loadingStrategy.load(
            data,
            this.resourceKey,
            options,
            this.structureStrategy.enhanceItem
        ).then(action((response) => {
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

    @action setActive(active: string | number) {
        this.active = active;
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
