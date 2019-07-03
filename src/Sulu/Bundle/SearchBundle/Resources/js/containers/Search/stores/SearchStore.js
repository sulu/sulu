// @flow
import {action, autorun, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';

class SearchStore {
    @observable query: ?string = undefined;
    @observable indexName: ?string = undefined;
    @observable result: Array<Object> = [];
    @observable loading: boolean = false;

    constructor() {
        autorun(() => {
            if (!this.query) {
                this.resetResults();
                return;
            }

            this.setLoading(true);
            ResourceRequester.getList('search', {q: this.query, index: this.indexName}).then(action((response) => {
                this.setLoading(false);
                this.result = response._embedded.result;
            }));
        });
    }

    @action search(query: ?string, index: ?string) {
        this.query = query;
        this.indexName = index;
    }

    @action resetResults() {
        this.result.splice(0, this.result.length);
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }
}

export default new SearchStore();
