// @flow
import {action, autorun, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import ResourceStore from '../../../stores/ResourceStore';
import type {Schema, SchemaTypes} from '../types';
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
    @observable type: string;
    @observable types: SchemaTypes = {};
    @observable schemaLoading: boolean = true;
    @observable typesLoading: boolean = true;
    schemaDisposer: () => void;

    constructor(resourceStore: ResourceStore) {
        this.resourceStore = resourceStore;

        this.schemaDisposer = autorun(() => {
            metadataStore.getSchema(this.resourceStore.resourceKey, this.type)
                .then(action((schema) => {
                    this.schema = schema;
                    const schemaFields = Object.keys(schema)
                        .reduce((data, key) => addSchemaProperties(data, key, schema), {});
                    this.resourceStore.data = {...schemaFields, ...this.resourceStore.data};
                    this.schemaLoading = false;
                }));
        });

        metadataStore.getSchemaTypes(this.resourceStore.resourceKey)
            .then(action((types) => {
                this.types = types;
                this.typesLoading = false;
            }));
    }

    destroy() {
        this.schemaDisposer();
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

    @action changeType(type: string) {
        if (Object.keys(this.types).length === 0) {
            throw new Error('The resource handled by this FormStore cannot handle types');
        }

        this.type = type;
    }
}
