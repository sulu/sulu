// @flow
import {action, observable, set} from 'mobx';
import log from 'loglevel';
import jsonpointer from 'json-pointer';
import {createAjv} from '../../../utils/Ajv';
import AbstractFormStore from './AbstractFormStore';
import type {ChangeContext, FormStoreInterface, Schema, SchemaType} from '../types';
import type {IObservableValue} from 'mobx/lib/mobx';

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

    @action change(dataPath: string, value: mixed, context?: ChangeContext) {
        const sanitizedDataPath = !dataPath.startsWith('/') ? '/' + dataPath : dataPath;

        jsonpointer.set( this.data, sanitizedDataPath, value );

        if (!context?.isDefaultValue && !context?.isServerValue) {
            this.dirty = true;
        }
    }

    @action changeMultiple(values: {[dataPath: string]: mixed}, context?: ChangeContext) {
        Object.keys(values).forEach((path) => {
            this.change(path, values[path], context);
        });
        set(this.data, this.data);
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
    }

    changeType() {
        throw new Error('The MemoryFormStore cannot handle types');
    }
}
