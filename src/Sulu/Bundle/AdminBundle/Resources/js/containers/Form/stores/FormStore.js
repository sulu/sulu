// @flow
import {action, observable} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {Schema} from '../types';

export default class FormStore {
    resourceKey: string;
    id: string;
    @observable data: Object = {};
    @observable dirty: boolean = false;

    constructor(resourceKey: string, id: string) {
        this.resourceKey = resourceKey;
        this.id = id;

        ResourceRequester.get(this.resourceKey, this.id).then(action((response) => {
            this.data = response;
        }));
    }

    changeSchema(schema: Schema) {
        const schemaFields = Object.keys(schema).reduce((object, key) => {
            object[key] = null;
            return object;
        }, {});

        this.data = {...schemaFields, ...this.data};
    }

    @action set(name: string, value: mixed) {
        this.data[name] = value;
        this.dirty = true;
    }
}
