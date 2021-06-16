// @flow
import {action, autorun, observable, set} from 'mobx';
import Ajv from 'ajv';
import jsonpointer from 'json-pointer';
import AbstractFormStore from './AbstractFormStore';
import type {FormStoreInterface, RawSchema, SchemaType} from '../types';
import type {IObservableValue} from 'mobx';

const ajv = new Ajv({allErrors: true, jsonPointers: true});

export default class MemoryFormStore extends AbstractFormStore implements FormStoreInterface {
    id = undefined;
    options = {};
    resourceKey = undefined;
    @observable data: {[string]: any};
    @observable dirty: boolean = false;
    @observable types: {[key: string]: SchemaType} = {};
    updateFieldPathEvaluationsDisposer: ?() => void;

    constructor(
        data: {[string]: any},
        rawSchema: RawSchema,
        jsonSchema: ?Object,
        locale: ?IObservableValue<string>,
        metadataOptions: ?{[string]: any}
    ) {
        super();

        this.data = data;
        this.loading = false;
        this.rawSchema = rawSchema;
        this.locale = locale;
        this.addMissingSchemaProperties();
        this.validator = jsonSchema ? ajv.compile(jsonSchema) : undefined;
        this.metadataOptions = metadataOptions;

        this.updateFieldPathEvaluationsDisposer = autorun(this.updateFieldPathEvaluations);
    }

    @action change(path: string, value: mixed) {
        this.set(path, value);
        this.dirty = true;
    }

    get hasInvalidType() {
        return false;
    }

    @action set(path: string, value: mixed) {
        jsonpointer.set(this.data, '/' + path, value);
    }

    @action setMultiple(data: Object) {
        Object.keys(data).forEach((path) => {
            this.set(path, data[path]);
        });
        set(this.data, this.data);

        super.setMultiple();
    }

    setType() {
        throw new Error('The MemoryFormStore cannot handle types');
    }

    destroy() {
        if (this.updateFieldPathEvaluationsDisposer) {
            this.updateFieldPathEvaluationsDisposer();
        }
    }
}
