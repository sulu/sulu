// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {Index} from '../types';

class IndexStore {
    indexPromise: ?Promise<Object>;

    clear() {
        this.indexPromise = undefined;
    }

    sendRequest(): Promise<Object> {
        if (!this.indexPromise) {
            this.indexPromise = ResourceRequester.getList('search_indexes');
        }

        return this.indexPromise;
    }

    loadIndexes(): Promise<Array<Index>> {
        return this.sendRequest().then((response: Object) => {
            return response._embedded.search_indexes;
        });
    }
}

export default new IndexStore();
