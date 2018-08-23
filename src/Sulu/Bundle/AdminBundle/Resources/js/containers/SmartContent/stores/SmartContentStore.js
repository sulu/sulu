// @flow
import {action, computed, observable, toJS} from 'mobx';
import type {IObservableValue} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {Conjunction, FilterCriteria, SortOrder} from '../types';

export default class SmartContentStore {
    locale: ?IObservableValue<string>;
    dataSourceResourceKey: ?string;
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
    @observable limit: ?number;

    constructor(filterCriteria: ?FilterCriteria, locale?: ?IObservableValue<string>, dataSourceResourceKey: ?string) {
        this.locale = locale;
        this.dataSourceResourceKey = dataSourceResourceKey;

        if (filterCriteria) {
            this.audienceTargeting = filterCriteria.audienceTargeting;
            this.categoryOperator = filterCriteria.categoryOperator;
            this.includeSubElements = filterCriteria.includeSubFolders;
            this.limit = filterCriteria.limitResult;
            this.sortBy = filterCriteria.sortBy;
            this.sortOrder = filterCriteria.sortMethod;
            this.tagOperator = filterCriteria.tagOperator;
            this.tags = filterCriteria.tags;

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
        };
    }
}
