// @flow
import {action, computed, observable} from 'mobx';
import type {FormStoreInterface, RawSchema, Schema, SchemaEntry} from '../types';
import metadataStore from './metadataStore';

export default class SchemaFormStore implements FormStoreInterface {
    @observable innerFormStore: ?FormStoreInterface;

    constructor(initializer: (schema: RawSchema, jsonSchema: Object) => FormStoreInterface, formKey: string) {
        Promise.all([
            metadataStore.getSchema(formKey),
            metadataStore.getJsonSchema(formKey),
        ]).then(action(([schema, jsonSchema]) => {
            this.innerFormStore = initializer(schema, jsonSchema);
        }));
    }

    change(name: string, value: mixed) {
        if (this.innerFormStore) {
            this.innerFormStore.change(name, value);
        }
    }

    @computed get data() {
        if (this.innerFormStore) {
            return this.innerFormStore.data;
        }

        return {};
    }

    set dirty(dirty: boolean) {
        if (this.innerFormStore) {
            this.innerFormStore.dirty = dirty;
        }
    }

    @computed get dirty() {
        if (this.innerFormStore) {
            return this.innerFormStore.dirty;
        }

        return false;
    }

    @computed get errors() {
        if (this.innerFormStore) {
            return this.innerFormStore.errors;
        }

        return [];
    }

    @computed get forbidden() {
        if (this.innerFormStore) {
            return this.innerFormStore.forbidden;
        }

        return false;
    }

    finishField(dataPath: string) {
        if (this.innerFormStore) {
            this.innerFormStore.finishField(dataPath);
        }
    }

    getPathsByTag(tagName: string) {
        if (this.innerFormStore) {
            return this.innerFormStore.getPathsByTag(tagName);
        }

        return [];
    }

    getSchemaEntryByPath(schemaPath: string): ?SchemaEntry {
        if (this.innerFormStore) {
            return this.innerFormStore.getSchemaEntryByPath(schemaPath);
        }

        return undefined;
    }

    getValueByPath(path: string): mixed {
        if (this.innerFormStore) {
            return this.innerFormStore.getValueByPath(path);
        }

        return false;
    }

    getValuesByTag(tagName: string): Array<mixed> {
        if (this.innerFormStore) {
            return this.innerFormStore.getValuesByTag(tagName);
        }

        return [];
    }

    @computed get id() {
        if (this.innerFormStore) {
            return this.innerFormStore.id;
        }

        return undefined;
    }

    isFieldModified(dataPath: string): boolean {
        if (this.innerFormStore) {
            return this.innerFormStore.isFieldModified(dataPath);
        }

        return false;
    }

    @computed get loading() {
        if (this.innerFormStore) {
            return this.innerFormStore.loading;
        }

        return true;
    }

    @computed get locale() {
        if (this.innerFormStore) {
            return this.innerFormStore.locale;
        }

        return undefined;
    }

    @computed get options() {
        if (this.innerFormStore) {
            return this.innerFormStore.options;
        }

        return {};
    }

    @computed get resourceKey() {
        if (this.innerFormStore) {
            return this.innerFormStore.resourceKey;
        }

        return undefined;
    }

    @computed.struct get schema(): Schema {
        if (this.innerFormStore) {
            return this.innerFormStore.schema;
        }

        return {};
    }

    validate() {
        if (this.innerFormStore) {
            return this.innerFormStore.validate();
        }

        return true;
    }
}
