// @flow
import {action, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import ResourceStore from '../../../stores/ResourceStore';
import type {Schema} from '../types';
import metadataStore from './MetadataStore';

function addSchemaProperties(data: Object, key: string, schema: Schema) {
    const type = schema[key].type;

    if (type !== 'section') {
        data[key] = null;
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
    @observable schemaLoading: boolean = true;

    constructor(resourceStore: ResourceStore) {
        this.resourceStore = resourceStore;

        metadataStore.getSchema(this.resourceStore.resourceKey)
            .then(action((schema) => {
                this.changeSchema(schema);
                this.schemaLoading = false;
            }));
    }

    @computed get loading(): boolean {
        return this.resourceStore.loading || this.schemaLoading;
    }

    @computed get data(): Object {
        return this.resourceStore.data;
    }

    save() {
        this.resourceStore.save();
    }

    set(name: string, value: mixed) {
        this.resourceStore.set(name, value);
    }

    @computed get locale(): ?IObservableValue<string> {
        return this.resourceStore.locale;
    }

    changeSchema(schema: Schema) {
        this.schema = schema;
        const schemaFields = Object.keys(schema).reduce((data, key) => addSchemaProperties(data, key, schema), {});

        this.resourceStore.data = {...schemaFields, ...this.resourceStore.data};
    }
}
