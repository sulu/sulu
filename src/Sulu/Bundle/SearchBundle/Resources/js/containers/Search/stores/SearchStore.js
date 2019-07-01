// @flow
import {action, autorun, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';

class SearchStore {
    @observable query: ?string = undefined;
    @observable result: Array<Object> = [];

    constructor() {
        autorun(() => {
            if (!this.query) {
                this.result = [];
                return;
            }

            ResourceRequester.getList('search', {q: this.query}).then(action((response) => {
                this.result = response._embedded.result;
            }));
        });
    }

    @action search(query: ?string) {
        this.query = query;
    }
}

export default new SearchStore();
