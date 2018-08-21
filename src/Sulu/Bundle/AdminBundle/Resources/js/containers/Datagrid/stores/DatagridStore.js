// @flow
import {action, autorun, computed, intercept, observable, untracked} from 'mobx';
import type {IObservableValue, IValueWillChange} from 'mobx';
import log from 'loglevel';
import ResourceRequester from '../../../services/ResourceRequester';
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
    @observable selections: Array<Object> = [];
    @observable dataLoading: boolean = true;
    @observable schemaLoading: boolean = true;
    @observable loadingStrategy: LoadingStrategyInterface;
    @observable structureStrategy: StructureStrategyInterface;
    @observable options: Object;
    active: IObservableValue<?string | number> = observable.box();
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
    initialSelectionIds: ?Array<string | number>;

    constructor(
        resourceKey: string,
        observableOptions: ObservableOptions,
        options: Object = {},
        selectionIds: ?Array<string | number>
    ) {
        this.resourceKey = resourceKey;
        this.observableOptions = observableOptions;
        this.options = options;
        this.initialSelectionIds = selectionIds;
        this.sendRequestDisposer = autorun(this.sendRequest);

        const callResetForChangedObservable = (change: IValueWillChange<*>) => {
            if (this.initialized && change.object.get() !== change.newValue) {
                this.reset();
            }
            return change;
        };

        const {locale} = this.observableOptions;
        if (locale) {
            this.localeDisposer = intercept(locale, '', callResetForChangedObservable);
        }

        this.searchDisposer = intercept(this.searchTerm, '', callResetForChangedObservable);
        this.sortColumnDisposer = intercept(this.sortColumn, '', callResetForChangedObservable);
        this.sortOrderDisposer = intercept(this.sortOrder, '', callResetForChangedObservable);

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

    @computed get visibleItems(): Array<*> {
        return this.structureStrategy.visibleItems;
    }

    @computed get activeItems(): ?Array<*> {
        return this.structureStrategy.activeItems;
    }

    @action updateStrategies = (
        loadingStrategy: LoadingStrategyInterface,
        structureStrategy: StructureStrategyInterface
    ) => {
        this.updateLoadingStrategy(loadingStrategy);
        this.updateStructureStrategy(structureStrategy);
    };

    @action updateLoadingStrategy = (loadingStrategy: LoadingStrategyInterface) => {
        if (this.loadingStrategy && this.loadingStrategy === loadingStrategy) {
            return;
        }

        if (this.loadingStrategy) {
            this.reset();
        }

        if (this.structureStrategy) {
            loadingStrategy.setStructureStrategy(this.structureStrategy);
            this.structureStrategy.clear();
        }

        this.loadingStrategy = loadingStrategy;
    };

    @action updateStructureStrategy = (structureStrategy: StructureStrategyInterface) => {
        if (this.structureStrategy === structureStrategy) {
            return;
        }

        if (this.loadingStrategy) {
            this.loadingStrategy.setStructureStrategy(structureStrategy);
        }

        this.structureStrategy = structureStrategy;
    };

    @action clear = () => {
        if (this.structureStrategy) {
            this.structureStrategy.clear();
        }
    };

    @action reset = () => {
        const page = this.getPage();

        this.clear();

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

    findById(id: string | number): ?Object {
        return this.structureStrategy.findById(id);
    }

    delete = (id: string | number): Promise<void> => {
        const queryOptions = {...this.options};

        const {locale} = this.observableOptions;
        if (locale) {
            queryOptions.locale = locale.get();
        }

        return ResourceRequester.delete(this.resourceKey, id, queryOptions)
            .then(action(() => {
                this.deselectById(id);
                this.remove(id);
            }));
    };

    move = (id: string | number, parentId: string | number) => {
        const queryOptions = {
            ...this.options,
            action: 'move',
            destination: parentId,
        };

        const {locale} = this.observableOptions;
        if (locale) {
            queryOptions.locale = locale.get();
        }

        return ResourceRequester.postWithId(this.resourceKey, id, queryOptions)
            .then(action(() => {
                this.activate(id);
                this.clear();
            }));
    };

    copy = (id: string | number, parentId: string | number) => {
        const queryOptions = {
            ...this.options,
            action: 'copy',
            destination: parentId,
        };

        const {locale} = this.observableOptions;
        if (locale) {
            queryOptions.locale = locale.get();
        }

        return ResourceRequester.postWithId(this.resourceKey, id, queryOptions)
            .then(action((response) => {
                // TODO do not hardcode "id", but use some metadata instead
                this.activate(response.id);
                this.clear();
            }));
    };

    @action deleteSelection = () => {
        const deletePromises = [];
        this.selectionIds.forEach((id) => {
            deletePromises.push(this.delete(id).catch((error) => {
                if (error.status !== 404) {
                    return Promise.reject(error);
                }
            }));
        });

        return Promise.all(deletePromises).then(() => {
            this.selectionIds.forEach(this.remove);
            this.clearSelection();
        });
    };

    remove = (identifier: string | number): void => {
        this.structureStrategy.remove(identifier);
    };

    sendRequest = () => {
        if (!this.initialized) {
            return;
        }

        const observableOptions = {};

        for (const key in this.observableOptions) {
            observableOptions[key] = this.observableOptions[key].get();
        }

        this.setDataLoading(true);

        const active = this.active.get();
        const options = {...observableOptions, ...this.options};

        if (this.initialSelectionIds) {
            options.selectedIds = this.initialSelectionIds.join(',');
        }

        if (!options.selectedIds) {
            if (active && untracked(() => !this.structureStrategy.findById(active))) {
                this.structureStrategy.clear();
                options.expandedIds = active;
            }

            if (!options.expandedIds && active) {
                options.parentId = active;
            }
        }

        options.sortBy = this.sortColumn.get();
        options.sortOrder = this.sortOrder.get();

        if (this.searchTerm.get()) {
            options.search = this.searchTerm.get();
        }

        log.info('Datagrid loads "' + this.resourceKey + '" data with the following options:', options);

        this.loadingStrategy.load(
            this.resourceKey,
            options,
            options.expandedIds ? undefined : active
        ).then(action((response) => {
            this.pageCount = response.pages;
            this.setDataLoading(false);

            if (this.initialSelectionIds) {
                this.initialSelectionIds
                    .map((selectionId) => this.findById(selectionId))
                    .forEach((selectionRow) => {
                        if (!selectionRow) {
                            return;
                        }

                        this.select(selectionRow);
                    });
                this.initialSelectionIds = undefined;
            }
        }));
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
        this.active.set(active);
    }

    @action activate(id: ?string | number) {
        // force reload by changing the active item to undefined before actually setting it
        this.setActive(undefined);
        this.setActive(id);

        if (this.structureStrategy.activate) {
            this.structureStrategy.activate(id);
        }
    }

    @action deactivate(id: ?string | number) {
        if (this.structureStrategy.deactivate) {
            this.structureStrategy.deactivate(id);
        }
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

    @action selectVisibleItems() {
        this.visibleItems.forEach((item) => {
            this.select(item);
        });
    }

    @action deselect(row: Object) {
        // TODO do not hardcode id but use metdata instead
        this.deselectById(row.id);
    }

    @action deselectById(id: string | number) {
        // TODO do not hardcode id but use metdata instead
        const index = this.selections.findIndex((item) => item.id === id);
        if (index === -1) {
            return;
        }

        this.selections.splice(index, 1);
    }

    @action deselectVisibleItems() {
        this.visibleItems.forEach((item) => {
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
