// @flow
import {action, autorun, computed, intercept, observable} from 'mobx';
import type {IObservableValue, IValueWillChange} from 'mobx';
import log from 'loglevel';
import type {
    LoadingStrategyInterface,
    ObservableOptions,
    Schema,
    SortOrder,
    StructureStrategyInterface,
} from '../types';
import metadataStore from './MetadataStore';

export default class DatagridStore {
    @observable pageCount: number = 0;
    @observable active: ?string | number = undefined;
    @observable selections: Array<Object> = [];
    @observable dataLoading: boolean = true;
    @observable schemaLoading: boolean = true;
    @observable loadingStrategy: LoadingStrategyInterface;
    @observable structureStrategy: StructureStrategyInterface;
    @observable options: Object;
    sortColumn: IObservableValue<string> = observable.box();
    sortOrder: IObservableValue<SortOrder> = observable.box();
    searchTerm: IObservableValue<?string> = observable.box();
    resourceKey: string;
    schema: Schema = {};
    observableOptions: ObservableOptions;
    localeDisposer: ?() => void;
    searchDisposer: () => void;
    sortColumnDisposer: () => void;
    sortOrderDisposer: () => void;
    sendRequestDisposer: () => void;

    constructor(
        resourceKey: string,
        observableOptions: ObservableOptions,
        options: Object = {}
    ) {
        this.resourceKey = resourceKey;
        this.observableOptions = observableOptions;
        this.options = options;
        this.sendRequestDisposer = autorun(this.sendRequest);

        const {locale} = this.observableOptions;
        if (locale) {
            this.localeDisposer = intercept(locale, '', (change: IValueWillChange<number>) => {
                if (locale.get() !== change.newValue) {
                    this.reset();
                }
                return change;
            });
        }

        this.searchDisposer = intercept(this.searchTerm, '', (change: IValueWillChange<string>) => {
            if (this.searchTerm.get() !== change.newValue) {
                this.reset();
            }
            return change;
        });

        this.sortColumnDisposer = intercept(this.sortColumn, '', (change: IValueWillChange<string>) => {
            if (this.sortColumn.get() !== change.newValue) {
                this.reset();
            }
            return change;
        });

        this.sortOrderDisposer = intercept(this.sortOrder, '', (change: IValueWillChange<SortOrder>) => {
            if (this.sortOrder.get() !== change.newValue) {
                this.reset();
            }
            return change;
        });

        metadataStore.getSchema(this.resourceKey)
            .then(action((schema) => {
                this.schema = schema;
                this.schemaLoading = false;
            }));
    }

    @computed get initialized(): boolean {
        return !!this.loadingStrategy && !!this.structureStrategy;
    }

    @computed get loading(): boolean {
        return this.dataLoading || this.schemaLoading;
    }

    @computed get data(): Array<*> {
        return this.structureStrategy.data;
    }

    @action updateStrategies = (
        loadingStrategy: LoadingStrategyInterface,
        structureStrategy: StructureStrategyInterface
    ) => {
        this.reset();
        this.updateLoadingStrategy(loadingStrategy);
        this.updateStructureStrategy(structureStrategy);
    };

    @action updateLoadingStrategy = (loadingStrategy: LoadingStrategyInterface) => {
        if (this.loadingStrategy && this.loadingStrategy === loadingStrategy) {
            return;
        }

        if (this.structureStrategy) {
            this.structureStrategy.clear();
        }

        this.loadingStrategy = loadingStrategy;
    };

    @action updateStructureStrategy = (structureStrategy: StructureStrategyInterface) => {
        if (this.structureStrategy === structureStrategy) {
            return;
        }

        this.structureStrategy = structureStrategy;
    };

    @action reset = () => {
        const page = this.getPage();

        if (this.structureStrategy) {
            this.structureStrategy.clear();
        }

        this.setActive(undefined);
        this.pageCount = 0;

        if (page && page > 1) {
            this.setPage(1);
        }
    };

    @action reload() {
        const page = this.getPage();

        this.reset();

        if (!page || page === 1) {
            this.sendRequest();
        }
    }

    findById(identifier: string | number): ?Object {
        return this.structureStrategy.findById(identifier);
    }

    sendRequest = () => {
        if (!this.initialized) {
            return;
        }

        const observableOptions = {};

        for (const key in this.observableOptions) {
            observableOptions[key] = this.observableOptions[key].get();
        }

        this.setDataLoading(true);

        const data = this.structureStrategy.getData(this.active);
        if (!data) {
            throw new Error('The active item does not exist in the Datagrid');
        }

        const options = {...observableOptions, ...this.options};
        if (this.active) {
            options.parent = this.active;
        }

        options.sortBy = this.sortColumn.get();
        options.sortOrder = this.sortOrder.get();

        if (this.searchTerm.get()) {
            options.search = this.searchTerm.get();
        }

        log.info('Datagrid loads "' + this.resourceKey + '" data with the following options:', options);

        this.loadingStrategy.load(
            data,
            this.resourceKey,
            options,
            this.structureStrategy.enhanceItem
        ).then(action((response) => {
            this.handleResponse(response);
        }));
    };

    handleResponse = (response: Object) => {
        this.pageCount = response.pages;
        this.setDataLoading(false);
    };

    @action setDataLoading(dataLoading: boolean) {
        this.dataLoading = dataLoading;
    }

    getPage() {
        return this.observableOptions.page.get();
    }

    @action setPage(page: number) {
        this.observableOptions.page.set(page);
    }

    @action setActive(active: ?string | number) {
        this.active = active;
    }

    @action sort(column: string, order: SortOrder) {
        this.sortColumn.set(column);
        this.sortOrder.set(order);
    }

    @action search(searchTerm: ?string) {
        if (searchTerm === this.searchTerm.get()) {
            return;
        }

        this.searchTerm.set(searchTerm);
    }

    @action select(row: Object) {
        // TODO do not hardcode id but use metdata instead
        if (this.selections.findIndex((item) => item.id === row.id) !== -1) {
            return;
        }

        this.selections.push(row);
    }

    @action selectEntirePage() {
        this.data.forEach((item) => {
            this.select(item);
        });
    }

    @action deselect(row: Object) {
        // TODO do not hardcode id but use metdata instead
        const index = this.selections.findIndex((item) => item.id === row.id);
        if (index === -1) {
            return;
        }

        this.selections.splice(index, 1);
    }

    @action deselectEntirePage() {
        this.data.forEach((item) => {
            this.deselect(item);
        });
    }

    @computed get selectionIds(): Array<string | number> {
        // TODO do not hardcode id but use metdata instead
        return this.selections.map((item) => item.id);
    }

    @action clearSelection() {
        this.selections = [];
    }

    clearData() {
        this.structureStrategy.clear();
    }

    destroy() {
        this.sendRequestDisposer();
        this.searchDisposer();
        this.sortColumnDisposer();
        this.sortOrderDisposer();

        if (this.localeDisposer) {
            this.localeDisposer();
        }
    }
}
