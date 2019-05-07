// @flow
import {action, autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import Ajv from 'ajv';
import jsonpointer from 'json-pointer';
import type {FormStoreInterface, RawSchema} from '../types';
import AbstractFormStore from './AbstractFormStore';

const ajv = new Ajv({allErrors: true, jsonPointers: true});

export default class MemoryFormStore extends AbstractFormStore implements FormStoreInterface {
    id = undefined;
    options = {};
    resourceKey = undefined;
    +loading: boolean;
    +locale: ?IObservableValue<string>;
    @observable data: Object;
    @observable dirty: boolean = false;
    updateFieldPathEvaluationsDisposer: ?() => void;

    constructor(
        data: Object,
        rawSchema: RawSchema,
        jsonSchema: ?Object,
        locale: ?IObservableValue<string>
    ) {
        super();

        this.data = data;
        this.loading = false;
        this.rawSchema = rawSchema;
        this.locale = locale;
        this.addMissingSchemaProperties();
        this.validator = jsonSchema ? ajv.compile(jsonSchema) : undefined;

        this.updateFieldPathEvaluationsDisposer = autorun(this.updateFieldPathEvaluations);
    }

    @action change(path: string, value: mixed) {
        jsonpointer.set(this.data, '/' + path, value);
        this.dirty = true;
    }

    destroy() {
        if (this.updateFieldPathEvaluationsDisposer) {
            this.updateFieldPathEvaluationsDisposer();
        }
    }
}
