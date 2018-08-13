// @flow
import 'core-js/library/fn/promise';
import {action, observable} from 'mobx';
import ResourceRequester from '../../services/ResourceRequester';

export default class SearchStore {
    resourceKey: string;
    searchProperties: Array<string>;
    @observable searchResults: Array<Object> = [];
    @observable loading: boolean = false;

    constructor(resourceKey: string, searchProperties: Array<string>) {
        this.resourceKey = resourceKey;
        this.searchProperties = searchProperties;
    }

    @action clearSearchResults = () => {
        this.searchResults.splice(0, this.searchResults.length);
    };

    @action search = (query: string): Promise<Array<Object>> => {
        const {resourceKey, searchProperties} = this;

        if (!query) {
            this.clearSearchResults();
            return Promise.resolve([]);
        }

        this.loading = true;

        return ResourceRequester.getList(resourceKey, {
            page: 1,
            limit: 10,
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
