// @flow
import {action, autorun, computed, observable, toJS, when} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import Ajv from 'ajv';
import jsonpointer from 'jsonpointer';
import log from 'loglevel';
import ResourceStore from '../../../stores/ResourceStore';
import type {Schema, SchemaEntry, SchemaTypes} from '../types';
import metadataStore from './MetadataStore';

// TODO do not hardcode "template", use some kind of metadata instead
const TYPE = 'template';
const SECTION_TYPE = 'section';

const ajv = new Ajv({allErrors: true, jsonPointers: true});

function addSchemaProperties(data: Object, key: string, schema: Schema) {
    const type = schema[key].type;

    if (type !== SECTION_TYPE) {
        data[key] = undefined;
    }

    const items = schema[key].items;

    if (type === SECTION_TYPE && items) {
        Object.keys(items)
            .reduce((object, childKey) => addSchemaProperties(data, childKey, items), data);
    }

    return data;
}

function sortObjectByPriority(a, b) {
    if (a.priority > b.priority) {
        return -1;
    }

    if (a.priority < b.priority) {
        return 1;
    }

    return 0;
}

function collectTagPathsWithPriority(
    tagName: string,
    data: Object,
    schema: Schema,
    parentPath: Array<string> = ['']
) {
    const pathsWithPriority = [];
    for (const key in schema) {
        const {items, tags, type, types} = schema[key];

        if (type === SECTION_TYPE && items) {
            pathsWithPriority.push(...collectTagPathsWithPriority(tagName, data, items, parentPath));
            continue;
        }

        if (types && data[key]) {
            for (const childKey of data[key].keys()) {
                const childData = data[key][childKey];
                pathsWithPriority.push(
                    ...collectTagPathsWithPriority(
                        tagName,
                        childData,
                        types[childData.type].form,
                        parentPath.concat([key, childKey])
                    )
                );
            }
            continue;
        }

        if (tags) {
            const filteredTags = tags.filter((tag) => tag.name === tagName);
            if (filteredTags.length === 0) {
                continue;
            }

            pathsWithPriority.push({
                path: parentPath.concat([key]).join('/'),
                priority: Math.max(...filteredTags.map((tag) => tag.priority || 0)),
            });
            continue;
        }
    }

    return pathsWithPriority.sort(sortObjectByPriority);
}

function collectTagPaths(
    tagName: string,
    data: Object,
    schema: Schema,
    parentPath: Array<string> = ['']
) {
    return collectTagPathsWithPriority(tagName, data, schema, parentPath)
        .map((pathWithPriority) => pathWithPriority.path);
}

export default class FormStore {
    resourceStore: ResourceStore;
    schema: Schema;
    validator: ?(data: Object) => boolean;
    @observable errors: Object = {};
    options: Object;
    @observable type: string;
    @observable types: SchemaTypes = {};
    @observable schemaLoading: boolean = true;
    @observable typesLoading: boolean = true;
    schemaDisposer: ?() => void;
    typeDisposer: ?() => void;
    pathsByTag: {[tagName: string]: Array<string>} = {};
    @observable modifiedFields: Array<string> = [];

    constructor(resourceStore: ResourceStore, options: Object = {}) {
        this.resourceStore = resourceStore;
        this.options = options;

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
                    (): void => this.setType(this.resourceStore.data[TYPE])
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
        this.pathsByTag = {};

        this.schema = schema;
        const schemaFields = Object.keys(schema)
            .reduce((data, key) => addSchemaProperties(data, key, schema), {});
        this.resourceStore.data = {...schemaFields, ...this.resourceStore.data};
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

    @action validate() {
        const {validator} = this;
        const errors = {};

        if (validator && !validator(toJS(this.data))) {
            for (const error of validator.errors) {
                switch (error.keyword) {
                    case 'oneOf':
                        // this only happens if a block has an invalid child field
                        // child fields already show error messages so we do not have to do it again for blocks
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

        if (Object.keys(this.errors).length > 0) {
            log.info('Form validation detected the following errors: ', toJS(this.errors));
        }
    }

    @action save(options: Object = {}): Promise<Object> {
        this.validate();

        if (Object.keys(this.errors).length > 0) {
            return Promise.reject('Errors occured when trying to save the data from the FormStore');
        }

        return this.resourceStore.save({...this.options, ...options}).then((response) => {
            const {modifiedFields} = this;
            modifiedFields.splice(0, modifiedFields.length);
            return response;
        });
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

    change(name: string, value: mixed) {
        this.resourceStore.change(name, value);
    }

    finishField(dataPath: string) {
        if (!this.modifiedFields.includes(dataPath)) {
            this.modifiedFields.push(dataPath);
        }
    }

    isFieldModified(dataPath: string): boolean {
        return this.modifiedFields.includes(dataPath);
    }

    @computed get locale(): ?IObservableValue<string> {
        return this.resourceStore.locale;
    }

    get resourceKey(): string {
        return this.resourceStore.resourceKey;
    }

    get id(): ?string | number {
        return this.resourceStore.id;
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

    getValueByPath = (path: string): mixed => {
        return jsonpointer.get(this.data, path);
    };

    getValuesByTag(tagName: string): Array<mixed> {
        return this.getPathsByTag(tagName).map(this.getValueByPath);
    }

    getPathsByTag(tagName: string) {
        const {data, schema} = this;
        if (!(tagName in this.pathsByTag)) {
            this.pathsByTag[tagName] = collectTagPaths(tagName, data, schema);
        }

        return this.pathsByTag[tagName];
    }

    getSchemaEntryByPath(schemaPath: string): SchemaEntry {
        return jsonpointer.get(this.schema, schemaPath);
    }
}
