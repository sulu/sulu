// @flow
import {action, autorun, computed, observable, toJS} from 'mobx';
import type {IObservableValue} from 'mobx';
import Requester from '../../../services/Requester';
import Config from '../../../services/Config';
import ResourceRequester from '../../../services/ResourceRequester';
import {buildQueryString} from '../../../utils/Request';
import type {Conjunction, FilterCriteria, SortOrder} from '../types';

export default class SmartContentStore {
    provider: string;
    locale: ?IObservableValue<string>;
    dataSourceResourceKey: ?string;
    @observable items: Array<Object> = [];
    @observable itemsLoading: boolean;
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
    itemDisposer: () => void;
    id: ?string | number;

    constructor(
        provider: string,
        filterCriteria: ?FilterCriteria,
        locale?: ?IObservableValue<string>,
        dataSourceResourceKey: ?string,
        id: ?string | number
    ) {
        this.provider = provider;
        this.locale = locale;
        this.dataSourceResourceKey = dataSourceResourceKey;
        this.id = id;

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
                    undefined,
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
                    filterCriteria.dataSource,
                    {locale: this.locale ? this.locale.get() : undefined}
                ).then(action((response) => {
                    this.dataSource = response;
                    this.dataSourceLoading = false;
                }));
            }
        }

        this.itemDisposer = autorun(this.loadItems);
    }

    destroy() {
        this.itemDisposer();
    }

    loadItems = () => {
        if (!this.hasFilterCriteria || this.loading) {
            return;
        }

        this.setItemsLoading(true);

        return Requester.get(
            Config.endpoints.items + buildQueryString({
                provider: this.provider,
                excluded: this.id,
                locale: this.locale,
                ...this.filterCriteria,
            })
        ).then(action((response) => {
            this.items = response._embedded.items;
            this.setItemsLoading(false);
        }));
    };

    @action setItemsLoading(itemsLoading: boolean) {
        this.itemsLoading = itemsLoading;
    }

    @computed get loading() {
        return this.dataSourceLoading || this.categoriesLoading;
    }

    @computed get filterCriteria(): FilterCriteria {
        return {
            audienceTargeting: this.audienceTargeting,
            categories: this.categories ? this.categories.map((category) => category.id) : undefined,
            categoryOperator: this.categoryOperator,
            dataSource: this.dataSource ? this.dataSource.id : undefined,
            includeSubFolders: this.includeSubElements,
            limitResult: this.limit,
            sortBy: this.sortBy,
            sortMethod: this.sortOrder,
            tagOperator: this.tagOperator,
            tags: toJS(this.tags),
            presentAs: this.presentation,
        };
    }

    @computed get hasFilterCriteria(): boolean {
        return this.filterCriteria.dataSource !== undefined
            || this.filterCriteria.categories !== undefined
            || this.filterCriteria.tags !== undefined;
    }
}
