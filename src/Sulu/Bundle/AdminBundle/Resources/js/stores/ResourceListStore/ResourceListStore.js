// @flow
import {action, observable} from 'mobx';
import ResourceRequester from '../../services/ResourceRequester';

export default class ResourceListStore {
    resourceKey: string;
    @observable loading: boolean = false;
    @observable data: Array<Object>;

    constructor(resourceKey: string) {
        this.resourceKey = resourceKey;

        this.loading = true;

        ResourceRequester.getList(resourceKey, {
            limit: 100,
            page: 1,
        }).then(action((response) => {
            this.data = response._embedded[resourceKey];
            this.loading = false;
        })).catch(action(() => {
            this.loading = false;
        }));
    }
}
