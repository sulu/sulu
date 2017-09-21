// @flow
import {action, observable} from 'mobx';
import type {Schema} from '../types';

export default class FormStore {
    @observable data: Object = {};
    @observable dirty: boolean = false;

    changeSchema(schema: Schema) {
        const data = {};
        Object.keys(schema).forEach((schemaKey) => {
            data[schemaKey] = null;
        });

        this.data = data;
    }

    @action set(name: string, value: mixed) {
        this.data[name] = value;
        this.dirty = true;
    }
}
