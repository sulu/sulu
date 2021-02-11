// @flow
import {action, autorun, computed, get, observable, when} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import jsonpointer from 'json-pointer';
import {createAjv} from '../../../utils';
import ResourceStore from '../../../stores/ResourceStore';
import type {FormStoreInterface, Schema, SchemaEntry, SchemaType, SchemaTypes} from '../types';
import AbstractFormStore from './AbstractFormStore';
import metadataStore from './metadataStore';

// TODO do not hardcode "template", use some kind of metadata instead
const TYPE = 'template';

const ajv = createAjv();

export default class ResourceFormStore extends AbstractFormStore implements FormStoreInterface {
    resourceStore: ResourceStore;
    formKey: string;
    options: {[string]: any};
    @observable type: string;
    @observable types: {[key: string]: SchemaType} = {};
    @observable schemaLoading: boolean = true;
    @observable typesLoading: boolean = true;
    schemaDisposer: ?() => void;
    typeDisposer: ?() => void;
    metadataOptions: ?{[string]: any};

    constructor(resourceStore: ResourceStore, formKey: string, options: Object = {}, metadataOptions: ?Object) {
        super();

        this.resourceStore = resourceStore;
        this.formKey = formKey;
        this.options = options;
        this.metadataOptions = metadataOptions;

        metadataStore.getSchemaTypes(this.formKey, this.metadataOptions)
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

    @action handleSchemaTypeResponse = (schemaTypes: ?SchemaTypes) => {
        const {
            types = {},
            defaultType,
        } = schemaTypes || {};

        this.types = types;
        this.typesLoading = false;

        if (this.hasTypes) {
            // this will set the correct type from the server response after it has been loaded
            when(
                () => !this.resourceStore.loading,
                (): void => {
                    this.setType(this.resourceStore.data[TYPE] || defaultType || Object.keys(this.types)[0]);
                }
            );
        }

        this.schemaDisposer = autorun(() => {
            const {type} = this;

            if (this.hasTypes && !type) {
                this.setSchemaLoading(false);
                return;
            }

            if (this.hasTypes && !this.types[type]) {
                this.setSchemaLoading(false);
                return;
            }

            this.setSchemaLoading(true);
            Promise.all([
                metadataStore.getSchema(this.formKey, type, this.metadataOptions),
                metadataStore.getJsonSchema(this.formKey, type, this.metadataOptions),
            ]).then(this.handleSchemaResponse);
        });
    };

    @action handleSchemaResponse = ([schema, jsonSchema]: [Schema, Object]) => {
        this.validator = jsonSchema ? ajv.compile(jsonSchema) : undefined;
        this.pathsByTag = {};

        this.schema = schema;
        this.addMissingSchemaProperties();
        this.setSchemaLoading(false);
    };

    @computed get hasTypes(): boolean {
        return Object.keys(this.types).length > 0;
    }

    @computed get hasInvalidType(): boolean {
        return !!this.types && !!this.type && !get(this.types, this.type);
    }

    @computed get loading(): boolean {
        return this.resourceStore.loading || this.schemaLoading;
    }

    @computed get data(): Object {
        return this.resourceStore.data;
    }

    @action save(options: Object = {}): Promise<Object> {
        if (!this.validate()) {
            return Promise.reject('Errors occured when trying to save the data from the FormStore');
        }

        return this.resourceStore.save({...this.options, ...options}).then((response) => {
            const {modifiedFields} = this;
            modifiedFields.splice(0, modifiedFields.length);
            return response;
        }).catch((errorResponse) => {
            return errorResponse.json().then(action((error) => {
                return Promise.reject(error);
            }));
        });
    }

    delete(options: Object): Promise<Object> {
        return this.resourceStore.delete({...this.options, ...options});
    }

    copyFromLocale(sourceLocale: string) {
        return this.resourceStore.copyFromLocale(sourceLocale, this.options)
            .then((response) => {
                if (this.hasTypes) {
                    this.setType(response[TYPE]);
                }
            });
    }

    set(name: string, value: mixed) {
        this.resourceStore.set(name, value);
    }

    setMultiple(data: Object) {
        this.resourceStore.setMultiple(data);
    }

    change(name: string, value: mixed) {
        this.resourceStore.change(name, value);
    }

    @computed get locale(): ?IObservableValue<string> {
        return this.resourceStore.locale;
    }

    @computed get resourceKey(): string {
        return this.resourceStore.resourceKey;
    }

    @computed get id(): ?string | number {
        return this.resourceStore.id;
    }

    @computed get saving(): boolean {
        return this.resourceStore.saving;
    }

    @computed get deleting(): boolean {
        return this.resourceStore.deleting;
    }

    @computed get forbidden(): boolean {
        return this.resourceStore.forbidden;
    }

    @computed get dirty(): boolean {
        return this.resourceStore.dirty;
    }

    set dirty(dirty: boolean) {
        this.resourceStore.dirty = dirty;
    }

    @action setSchemaLoading(schemaLoading: boolean) {
        this.schemaLoading = schemaLoading;
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
                'The form "' + this.formKey + '" handled by this ResourceFormStore cannot handle types'
            );
        }
    }

    getSchemaEntryByPath(schemaPath: string): SchemaEntry {
        return jsonpointer.get(this.schema, schemaPath);
    }
}
