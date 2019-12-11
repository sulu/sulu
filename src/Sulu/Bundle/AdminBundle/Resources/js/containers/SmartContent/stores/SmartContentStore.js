// @flow
import {action, autorun, computed, observable, toJS} from 'mobx';
import type {IObservableValue} from 'mobx';
import equals from 'fast-deep-equal';
import Requester from '../../../services/Requester';
import Config from '../../../services/Config';
import ResourceRequester from '../../../services/ResourceRequester';
import {buildQueryString} from '../../../utils/Request';
import type {Conjunction, FilterCriteria, SortOrder} from '../types';

export default class SmartContentStore {
    provider: string;
    locale: ?IObservableValue<string>;
    dataSourceResourceKey: ?string;
    params: Object;
    @observable items: Array<Object> = [];
    @observable itemsLoading: boolean = true;
    @observable categoriesLoading: boolean;
    @observable dataSourceLoading: boolean;
    @observable dataSource: ?Object;
    @observable includeSubElements: ?boolean;
    @observable categories: ?Array<Object>;
    @observable categoryOperator: ?Conjunction;
    @observable tags: ?Array<string | number>;
    @observable tagOperator: ?Conjunction;
    @observable audienceTargeting: ?boolean;
    @observable sortBy: ?string;
    @observable sortOrder: ?SortOrder;
    @observable presentation: ?string;
    @observable limit: ?number;
    @observable excludedIds: Array<string | number> = [];
    itemDisposer: ?() => void;
    id: ?string | number;

    constructor(
        provider: string,
        filterCriteria: ?FilterCriteria,
        locale?: ?IObservableValue<string>,
        dataSourceResourceKey: ?string,
        id: ?string | number,
        params: Object
    ) {
        this.provider = provider;
        this.locale = locale;
        this.dataSourceResourceKey = dataSourceResourceKey;
        this.id = id;
        this.params = params;

        if (filterCriteria) {
            this.audienceTargeting = filterCriteria.audienceTargeting;
            this.categoryOperator = filterCriteria.categoryOperator;
            this.includeSubElements = filterCriteria.includeSubFolders;
            this.limit = filterCriteria.limitResult;
            this.sortBy = filterCriteria.sortBy;
            this.sortOrder = filterCriteria.sortMethod;
            this.tagOperator = filterCriteria.tagOperator;
            this.tags = filterCriteria.tags;
            this.presentation = filterCriteria.presentAs;

            if (filterCriteria.categories) {
                this.categoriesLoading = true;
                // TODO extract 'categories' into some kind of variable?
                ResourceRequester.get(
                    'categories',
                    {
                        ids: filterCriteria.categories,
                        locale: this.locale ? this.locale.get() : undefined,
                    }
                ).then(action((response) => {
                    this.categoriesLoading = false;
                    this.categories = response._embedded.categories;
                }));
            }

            if (filterCriteria.dataSource && this.dataSourceResourceKey) {
                this.dataSourceLoading = true;
                ResourceRequester.get(
                    this.dataSourceResourceKey,
                    {id: filterCriteria.dataSource, locale: this.locale ? this.locale.get() : undefined}
                ).then(action((response) => {
                    this.dataSource = response;
                    this.dataSourceLoading = false;
                })).catch(action(() => {
                    this.dataSourceLoading = false;
                }));
            }
        }
    }

    start() {
        this.itemDisposer = autorun(this.loadItems);
    }

    destroy() {
        if (this.itemDisposer) {
            this.itemDisposer();
        }
    }

    loadItems = () => {
        if (this.loading) {
            this.setItems([]);
            return;
        }

        this.setItemsLoading(true);

        return Requester.get(
            Config.endpoints.items + buildQueryString({
                provider: this.provider,
                excluded: [this.id, ...this.excludedIds],
                locale: this.locale,
                params: JSON.stringify(this.params),
                ...this.filterCriteria,
            })
        ).then(action((response) => {
            this.setItems(response._embedded.items);
            this.setItemsLoading(false);
        }));
    };

    @action setItems(items: Array<Object>) {
        this.items = items;
    }

    @action setItemsLoading(itemsLoading: boolean) {
        this.itemsLoading = itemsLoading;
    }

    @action setExcludedIds(excludedIds: Array<string | number>) {
        if (equals(toJS(this.excludedIds), excludedIds)) {
            return;
        }

        this.excludedIds = excludedIds;
    }

    @computed get loading() {
        return !!this.dataSourceLoading || !!this.categoriesLoading;
    }

    @computed get filterCriteria(): FilterCriteria {
        return {
            audienceTargeting: this.audienceTargeting,
            categories: this.categories && this.categories.length > 0
                ? this.categories.map((category) => category.id)
                : undefined,
            categoryOperator: this.categoryOperator,
            dataSource: this.dataSource ? this.dataSource.id : undefined,
            includeSubFolders: this.includeSubElements,
            limitResult: this.limit,
            sortBy: this.sortBy,
            sortMethod: this.sortOrder,
            tagOperator: this.tagOperator,
            tags: this.tags && this.tags.length > 0 ? toJS(this.tags) : undefined,
            presentAs: this.presentation,
        };
    }
}
