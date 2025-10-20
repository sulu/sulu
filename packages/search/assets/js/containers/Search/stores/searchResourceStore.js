// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {SearchResource} from '../types';

class SearchResourceStore {
    searchResourcePromise: ?Promise<Object>;

    clear() {
        this.searchResourcePromise = undefined;
    }

    sendRequest(): Promise<Object> {
        if (!this.searchResourcePromise) {
            this.searchResourcePromise = ResourceRequester.getList('search_resources');
        }

        return this.searchResourcePromise;
    }

    loadSearchResources(): Promise<Array<SearchResource>> {
        return this.sendRequest().then((response: Object) => {
            return response._embedded.search_resources;
        });
    }
}

export default new SearchResourceStore();
