// @flow
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import jsonpointer from 'json-pointer';
import {createAjv} from '../../../utils';
import type {FormStoreInterface, Schema, SchemaType} from '../types';
import AbstractFormStore from './AbstractFormStore';

const ajv = createAjv();

export default class MemoryFormStore extends AbstractFormStore implements FormStoreInterface {
    id = undefined;
    options = {};
    resourceKey = undefined;
    @observable data: {[string]: any};
    @observable dirty: boolean = false;
    @observable types: {[key: string]: SchemaType} = {};

    constructor(
        data: {[string]: any},
        schema: Schema,
        jsonSchema: ?Object,
        locale: ?IObservableValue<string>,
        metadataOptions: ?{[string]: any}
    ) {
        super();

        this.data = data;
        this.loading = false;
        this.schema = schema;
        this.locale = locale;
        this.addMissingSchemaProperties();
        this.validator = jsonSchema ? ajv.compile(jsonSchema) : undefined;
        this.metadataOptions = metadataOptions;
    }

    @action change(path: string, value: mixed) {
        jsonpointer.set(this.data, '/' + path, value);
        this.dirty = true;
    }

    get hasInvalidType() {
        return false;
    }

    @action setMultiple(data: Object) {
        this.data = {...this.data, ...data};
    }

    setType() {
        throw new Error('The MemoryFormStore cannot handle types');
    }
}
