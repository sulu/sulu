// @flow
import {action, autorun, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';

class SearchStore {
    @observable query: ?string = undefined;
    @observable index: ?string = undefined;
    @observable result: Array<Object> = [];

    constructor() {
        autorun(() => {
            if (!this.query) {
                this.resetResults();
                return;
            }

            ResourceRequester.getList('search', {q: this.query, index: this.index}).then(action((response) => {
                this.result = response._embedded.result;
            }));
        });
    }

    @action search(query: ?string, index: ?string) {
        this.query = query;
        this.index = index;
    }

    @action resetResults() {
        this.result.splice(0, this.result.length);
    }
}

export default new SearchStore();
