// @flow
import {action, observable} from 'mobx';
import ResourceRequester from '../../services/ResourceRequester';

export default class ResourceListStore {
    resourceKey: string;
    @observable loading: boolean = false;
    @observable data: Array<Object>;

    constructor(resourceKey: string, apiOptions: Object = {}) {
        this.resourceKey = resourceKey;

        this.loading = true;

        if (!apiOptions.hasOwnProperty('limit')) {
            apiOptions.limit = 100;
        }
        if (!apiOptions.hasOwnProperty('page')) {
            apiOptions.page = 1;
        }

        ResourceRequester.getList(resourceKey, apiOptions).then(action((response) => {
            this.data = response._embedded[resourceKey];
            this.loading = false;
        })).catch(action(() => {
            this.loading = false;
        }));
    }
}
