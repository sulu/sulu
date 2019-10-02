// @flow
import {action, computed, observable} from 'mobx';
import ResourceRequester from '../../services/ResourceRequester';

export default class ResourceListStore {
    requestParameters: Object;
    resourceKey: string;
    idProperty: string;
    @observable initialLoading: boolean = false;
    @observable deleting: boolean = false;
    @observable patching: boolean = false;
    @observable data: Array<Object>;

    @computed get loading() {
        return this.initialLoading || this.deleting || this.patching;
    }

    constructor(resourceKey: string, requestParameters: Object = {}, idProperty: string = 'id') {
        this.resourceKey = resourceKey;
        this.requestParameters = requestParameters;
        this.idProperty = idProperty;

        this.initialLoading = true;
        ResourceRequester.getList(resourceKey, requestParameters).then(action((response) => {
            this.data = response._embedded[resourceKey];
            this.initialLoading = false;
        })).catch(action(() => {
            this.initialLoading = false;
        }));
    }

    @action deleteList(ids: Array<string | number>) {
        this.deleting = true;
        return ResourceRequester.deleteList(this.resourceKey, {...this.requestParameters, ids}).then(action(() => {
            for (const id of ids) {
                this.data.splice(this.data.findIndex((object) => object[this.idProperty] === id), 1);
            }

            this.deleting = false;
        }));
    }

    @action patchList(data: Array<Object>) {
        this.patching = true;
        return ResourceRequester.patchList(this.resourceKey, data).then(action((response) => {
            for (const object of response) {
                const index = this.data
                    .findIndex((oldObject) => oldObject[this.idProperty] === object[this.idProperty]);

                if (index === -1) {
                    this.data.push(object);
                } else {
                    this.data[index] = object;
                }
            }

            this.patching = false;
        }));
    }
}
