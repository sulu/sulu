// @flow
import {action, autorun, computed, observable, when} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import Ajv from 'ajv';
import jsonpointer from 'json-pointer';
import ResourceStore from '../../../stores/ResourceStore';
import type {FormStoreInterface, RawSchema, SchemaEntry, SchemaTypes} from '../types';
import AbstractFormStore from './AbstractFormStore';
import metadataStore from './metadataStore';

// TODO do not hardcode "template", use some kind of metadata instead
const TYPE = 'template';

const ajv = new Ajv({allErrors: true, jsonPointers: true});

export default class ResourceFormStore extends AbstractFormStore implements FormStoreInterface {
    resourceStore: ResourceStore;
    formKey: string;
    options: Object;
    @observable type: string;
    @observable types: SchemaTypes = {};
    @observable schemaLoading: boolean = true;
    @observable typesLoading: boolean = true;
    schemaDisposer: ?() => void;
    typeDisposer: ?() => void;
    updateFieldPathEvaluationsDisposer: ?() => void;

    constructor(resourceStore: ResourceStore, formKey: string, options: Object = {}) {
        super();

        this.resourceStore = resourceStore;
        this.formKey = formKey;
        this.options = options;

        metadataStore.getSchemaTypes(this.formKey)
            .then(this.handleSchemaTypeResponse);
    }

    destroy() {
        if (this.schemaDisposer) {
            this.schemaDisposer();
        }

        if (this.typeDisposer) {
            this.typeDisposer();
        }

        if (this.updateFieldPathEvaluationsDisposer) {
            this.updateFieldPathEvaluationsDisposer();
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
                    (): void => this.setType(this.resourceStore.data[TYPE])
                );
            }
        }

        this.schemaDisposer = autorun(() => {
            const {type} = this;

            if (this.hasTypes && !type) {
                return;
            }

            Promise.all([
                metadataStore.getSchema(this.formKey, type),
                metadataStore.getJsonSchema(this.formKey, type),
            ]).then(this.handleSchemaResponse);
        });
    };

    @action handleSchemaResponse = ([schema, jsonSchema]: [RawSchema, Object]) => {
        this.validator = jsonSchema ? ajv.compile(jsonSchema) : undefined;
        this.pathsByTag = {};

        this.rawSchema = schema;
        this.addMissingSchemaProperties();
        this.schemaLoading = false;

        this.updateFieldPathEvaluationsDisposer = autorun(this.updateFieldPathEvaluations);
    };

    @computed get hasTypes(): boolean {
        return Object.keys(this.types).length > 0;
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

    delete(): Promise<Object> {
        return this.resourceStore.delete(this.options);
    }

    copyFromLocale(locale: string) {
        return this.resourceStore.copyFromLocale(locale, this.options)
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

    @computed get dirty(): boolean {
        return this.resourceStore.dirty;
    }

    set dirty(dirty: boolean) {
        this.resourceStore.dirty = dirty;
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
