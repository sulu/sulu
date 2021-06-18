// @flow
import {action, autorun, observable, set} from 'mobx';
import Ajv from 'ajv';
import log from 'loglevel';
import jsonpointer from 'json-pointer';
import AbstractFormStore from './AbstractFormStore';
import type {ChangeContext, FormStoreInterface, RawSchema, SchemaType} from '../types';

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

    @action change(path: string, value: mixed, context?: ChangeContext) {
        jsonpointer.set(this.data, '/' + path, value);

        if (!context?.isDefaultValue) {
            this.dirty = true;
        }
    }

    @action changeMultiple(data: Object, context?: ChangeContext) {
        Object.keys(data).forEach((path) => {
            this.change(path, data[path], context);
        });
        set(this.data, this.data);

        super.changeMultiple();
    }

    get hasInvalidType() {
        return false;
    }

    /**
     * @deprecated
     */
    @action setMultiple(data: Object) {
        log.warn(
            'The "setMultiple" method is deprecated and will be removed. ' +
            'Use the "changeMultiple" method instead.'
        );

        this.data = {...this.data, ...data};

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
