// @flow
import {action, autorun, computed, observable, toJS, when} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import Ajv from 'ajv';
import jsonpointer from 'jsonpointer';
import ResourceStore from '../../../stores/ResourceStore';
import type {Schema, SchemaTypes} from '../types';
import metadataStore from './MetadataStore';

// TODO do not hardcode "template", use some kind of metadata instead
const TYPE = 'template';

const ajv = new Ajv({allErrors: true, jsonPointers: true});

function addSchemaProperties(data: Object, key: string, schema: Schema) {
    const type = schema[key].type;

    if (type !== 'section') {
        data[key] = undefined;
    }

    const items = schema[key].items;

    if (type === 'section' && items) {
        Object.keys(items)
            .reduce((object, childKey) => addSchemaProperties(data, childKey, items), data);
    }

    return data;
}

export default class FormStore {
    resourceStore: ResourceStore;
    schema: Schema;
    validator: ?(data: Object) => boolean;
    @observable errors: Object;
    @observable type: string;
    @observable types: SchemaTypes = {};
    @observable schemaLoading: boolean = true;
    @observable typesLoading: boolean = true;
    schemaDisposer: ?() => void;
    typeDisposer: ?() => void;

    constructor(resourceStore: ResourceStore) {
        this.resourceStore = resourceStore;

        metadataStore.getSchemaTypes(this.resourceStore.resourceKey)
            .then(this.handleSchemaTypeResponse);
    }

    destroy() {
        if (this.schemaDisposer) {
            this.schemaDisposer();
        }

        if (this.typeDisposer) {
            this.typeDisposer();
        }
    }

    @action handleSchemaTypeResponse = (types: SchemaTypes) => {
        this.types = types;
        this.typesLoading = false;

        if (this.hasTypes) {
            // this will set the correct type from the server response after it has been loaded
            if (this.resourceStore.id) {
                when(
                    () => !this.resourceStore.loading,
                    () => this.setType(this.resourceStore.data[TYPE])
                );
            } else if (this.defaultType) {
                this.setType(this.defaultType);
            }
        }

        this.schemaDisposer = autorun(() => {
            const {type} = this;

            if (this.hasTypes && !type) {
                return;
            }

            Promise.all([
                metadataStore.getSchema(this.resourceStore.resourceKey, type),
                metadataStore.getJsonSchema(this.resourceStore.resourceKey, type),
            ]).then(this.handleSchemaResponse);
        });
    };

    @action handleSchemaResponse = ([schema, jsonSchema]: [Schema, Object]) => {
        this.validator = ajv.compile(jsonSchema);

        this.schema = schema;
        const schemaFields = Object.keys(schema)
            .reduce((data, key) => addSchemaProperties(data, key, schema), {});
        const newData = {...schemaFields, ...this.resourceStore.data};
        this.resourceStore.data = this.hasType ? {[TYPE]: this.defaultType, ...newData} : newData;
        this.schemaLoading = false;
    };

    @computed get hasTypes(): boolean {
        return Object.keys(this.types).length > 0;
    }

    @computed get defaultType(): ?string {
        if (!this.hasTypes) {
            return undefined;
        }

        return Object.keys(this.types)[0];
    }

    @computed get loading(): boolean {
        return this.resourceStore.loading || this.schemaLoading;
    }

    @computed get data(): Object {
        return this.resourceStore.data;
    }

    validate() {
        const {validator} = this;
        const errors = {};

        if (validator && !validator(toJS(this.data))) {
            for (const error of validator.errors) {
                switch (error.keyword) {
                    case 'oneOf':
                        break;
                    case 'required':
                        jsonpointer.set(
                            errors,
                            error.dataPath + '/' + error.params.missingProperty,
                            {keyword: error.keyword, parameters: error.params}
                        );
                        break;
                    default:
                        jsonpointer.set(
                            errors,
                            error.dataPath,
                            {keyword: error.keyword, parameters: error.params}
                        );
                }
            }
        }

        this.errors = errors;
    }

    @action save(): Promise<Object> {
        this.validate();

        if (Object.keys(this.errors).length > 0) {
            return Promise.reject('Errors occured when trying to save the data from the FormStore');
        }

        return this.resourceStore.save();
    }

    set(name: string, value: mixed) {
        this.resourceStore.set(name, value);
    }

    change(name: string, value: mixed) {
        this.resourceStore.change(name, value);
    }

    @computed get locale(): ?IObservableValue<string> {
        return this.resourceStore.locale;
    }

    @action setType(type: string) {
        this.validateTypes();
        this.type = type;
        this.set(TYPE, type);
    }

    @action changeType(type: string) {
        this.validateTypes();
        this.type = type;
        this.change(TYPE, type);
    }

    validateTypes() {
        if (Object.keys(this.types).length === 0) {
            throw new Error(
                'The resource "' + this.resourceStore.resourceKey + '" handled by this FormStore cannot handle types'
            );
        }
    }
}
