// @flow
import {action, observable} from 'mobx';
import ResourceRequester from '../../services/ResourceRequester';
import type {IObservableValue} from 'mobx/lib/mobx';

export default class SearchStore {
    resourceKey: string;
    searchProperties: Array<string>;
    options: Object;
    locale: ?IObservableValue<string>;
    @observable searchResults: Array<Object> = [];
    @observable loading: boolean = false;

    constructor(
        resourceKey: string,
        searchProperties: Array<string>,
        options: Object = {},
        locale: ?IObservableValue<string>
    ) {
        this.resourceKey = resourceKey;
        this.searchProperties = searchProperties;
        this.options = options;
        this.locale = locale;
    }

    @action clearSearchResults = () => {
        this.searchResults.splice(0, this.searchResults.length);
    };

    @action search = (query: string, excludedIds: ?Array<string | number> = undefined): Promise<Array<Object>> => {
        const {resourceKey, searchProperties} = this;

        if (!query) {
            this.clearSearchResults();
            return Promise.resolve([]);
        }

        this.loading = true;

        return ResourceRequester.getList(resourceKey, {
            ...this.options,
            excludedIds,
            locale: this.locale ? this.locale.get() : undefined,
            limit: 10,
            page: 1,
            searchFields: searchProperties,
            search: query,
        }).then(action((response) => {
            this.clearSearchResults();
            this.searchResults.push(...response._embedded[resourceKey]);
            this.loading = false;
            return this.searchResults;
        })).catch(action(() => {
            this.loading = false;
        })).then(() => {
            return [];
        });
    };
}
