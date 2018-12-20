// @flow
import {action, autorun, computed, observable, toJS, untracked, when} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import Ajv from 'ajv';
import jsonpointer from 'jsonpointer';
import log from 'loglevel';
import jexl from 'jexl';
import ResourceStore from '../../../stores/ResourceStore';
import type {RawSchema, RawSchemaEntry, Schema, SchemaEntry, SchemaTypes} from '../types';
import metadataStore from './MetadataStore';

// TODO do not hardcode "template", use some kind of metadata instead
const TYPE = 'template';
const SECTION_TYPE = 'section';

const ajv = new Ajv({allErrors: true, jsonPointers: true});

function addSchemaProperties(data: Object, key: string, schema: RawSchema) {
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
    schema: RawSchema,
    parentPath: Array<string> = ['']
) {
    const pathsWithPriority = [];
    for (const key in schema) {
        const {items, tags, type, types} = schema[key];

        if (type === SECTION_TYPE && items) {
            pathsWithPriority.push(...collectTagPathsWithPriority(tagName, data, items, parentPath));
            continue;
        }

        if (types && Object.keys(types).length > 0 && data[key]) {
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
    schema: RawSchema,
    parentPath: Array<string> = ['']
) {
    return collectTagPathsWithPriority(tagName, data, schema, parentPath)
        .map((pathWithPriority) => pathWithPriority.path);
}

function transformRawSchema(
    rawSchema: RawSchema,
    disabledFieldPaths: Array<string>,
    hiddenFieldPaths: Array<string>,
    basePath: string = ''
): Schema {
    return Object.keys(rawSchema).reduce((schema, schemaKey) => {
        schema[schemaKey] = transformRawSchemaEntry(
            rawSchema[schemaKey],
            disabledFieldPaths,
            hiddenFieldPaths,
            basePath + '/' + schemaKey
        );

        return schema;
    }, {});
}

function transformRawSchemaEntry(
    rawSchemaEntry: RawSchemaEntry,
    disabledFieldPaths: Array<string>,
    hiddenFieldPaths: Array<string>,
    path: string
): SchemaEntry {
    return Object.keys(rawSchemaEntry).reduce((schemaEntry, schemaEntryKey) => {
        if (schemaEntryKey === 'disabledCondition') {
            // jexl could be directly used here, if it would support synchrounous execution
            schemaEntry.disabled = disabledFieldPaths.includes(path);
        } else if (schemaEntryKey === 'visibleCondition') {
            // jexl could be directly used here, if it would support synchrounous execution
            schemaEntry.visible = !hiddenFieldPaths.includes(path);
        } else if (schemaEntryKey === 'items' && rawSchemaEntry.items) {
            schemaEntry.items = transformRawSchema(rawSchemaEntry.items, disabledFieldPaths, hiddenFieldPaths, path);
        } else if (schemaEntryKey === 'types' && rawSchemaEntry.types) {
            const rawSchemaEntryTypes = rawSchemaEntry.types;

            schemaEntry.types = Object.keys(rawSchemaEntryTypes).reduce((schemaEntryTypes, schemaEntryTypeKey) => {
                schemaEntryTypes[schemaEntryTypeKey] = {
                    title: rawSchemaEntryTypes[schemaEntryTypeKey].title,
                    form: transformRawSchema(
                        rawSchemaEntryTypes[schemaEntryTypeKey].form,
                        disabledFieldPaths,
                        hiddenFieldPaths,
                        path + '/types/' + schemaEntryTypeKey + '/form'
                    ),
                };

                return schemaEntryTypes;
            }, {});
        } else {
            // $FlowFixMe
            schemaEntry[schemaEntryKey] = rawSchemaEntry[schemaEntryKey];
        }

        return schemaEntry;
    }, {});
}

function evaluateFieldConditions(rawSchema: RawSchema, locale: ?string, data: Object, basePath: string = '') {
    const visibleConditionPromises = [];
    const disabledConditionPromises = [];

    Object.keys(rawSchema).forEach((schemaKey) => {
        const {disabledCondition, items, types, visibleCondition} = rawSchema[schemaKey];
        const schemaPath = basePath + '/' + schemaKey;

        const evaluationData = {...data, __locale: locale};

        if (disabledCondition) {
            disabledConditionPromises.push(jexl.eval(disabledCondition, evaluationData).then((result) => {
                if (result) {
                    return Promise.resolve(schemaPath);
                }
            }));
        }

        if (visibleCondition) {
            visibleConditionPromises.push(jexl.eval(visibleCondition, evaluationData).then((result) => {
                if (!result) {
                    return Promise.resolve(schemaPath);
                }
            }));
        }

        if (items) {
            const {
                disabledConditionPromises: itemDisabledConditionPromises,
                visibleConditionPromises: itemVisibleConditionPromises,
            } = evaluateFieldConditions(items, locale, data, schemaPath);

            disabledConditionPromises.push(...itemDisabledConditionPromises);
            visibleConditionPromises.push(...itemVisibleConditionPromises);
        }

        if (types) {
            Object.keys(types).forEach((type) => {
                const {
                    disabledConditionPromises: typeDisabledConditionPromises,
                    visibleConditionPromises: typeVisibleConditionPromises,
                } = evaluateFieldConditions(types[type].form, locale, data, schemaPath + '/types/' + type + '/form');

                disabledConditionPromises.push(...typeDisabledConditionPromises);
                visibleConditionPromises.push(...typeVisibleConditionPromises);
            });
        }
    });

    return {
        disabledConditionPromises,
        visibleConditionPromises,
    };
}

export default class FormStore {
    resourceStore: ResourceStore;
    formKey: string;
    rawSchema: RawSchema;
    validator: ?(data: Object) => boolean;
    @observable errors: Object = {};
    options: Object;
    @observable type: string;
    @observable types: SchemaTypes = {};
    @observable schemaLoading: boolean = true;
    @observable typesLoading: boolean = true;
    schemaDisposer: ?() => void;
    typeDisposer: ?() => void;
    updateFieldPathEvaluationsDisposer: ?() => void;
    pathsByTag: {[tagName: string]: Array<string>} = {};
    @observable modifiedFields: Array<string> = [];
    @observable disabledFieldPaths: Array<string> = [];
    @observable hiddenFieldPaths: Array<string> = [];

    constructor(resourceStore: ResourceStore, formKey: string, options: Object = {}) {
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
                metadataStore.getSchema(this.formKey, type),
                metadataStore.getJsonSchema(this.formKey, type),
            ]).then(this.handleSchemaResponse);
        });
    };

    @action handleSchemaResponse = ([schema, jsonSchema]: [RawSchema, Object]) => {
        this.validator = jsonSchema ? ajv.compile(jsonSchema) : undefined;
        this.pathsByTag = {};

        this.rawSchema = schema;
        const schemaFields = Object.keys(schema)
            .reduce((data, key) => addSchemaProperties(data, key, schema), {});
        this.resourceStore.data = {...schemaFields, ...this.resourceStore.data};
        this.schemaLoading = false;

        this.updateFieldPathEvaluationsDisposer = autorun(this.updateFieldPathEvaluations);
    };

    @computed get schema(): Schema {
        return transformRawSchema(this.rawSchema, this.disabledFieldPaths, this.hiddenFieldPaths);
    }

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

    finishField(dataPath: string): Promise<*> {
        if (!this.modifiedFields.includes(dataPath)) {
            this.modifiedFields.push(dataPath);
        }

        return this.updateFieldPathEvaluations();
    }

    isFieldModified(dataPath: string): boolean {
        return this.modifiedFields.includes(dataPath);
    }

    updateFieldPathEvaluations = (): Promise<*> => {
        if (this.loading) {
            return Promise.resolve();
        }

        const {
            disabledConditionPromises,
            visibleConditionPromises,
        } = evaluateFieldConditions(
            this.rawSchema,
            this.locale ? this.locale.get() : undefined,
            untracked(() => toJS(this.data))
        );

        const disabledConditionsPromise = Promise.all(disabledConditionPromises)
            .then(action((disabledConditionResults) => {
                this.disabledFieldPaths = disabledConditionResults;
            }));

        const visibleConditionsPromise = Promise.all(visibleConditionPromises)
            .then(action((visibleConditionResults) => {
                this.hiddenFieldPaths = visibleConditionResults;
            }));

        return Promise.all([disabledConditionsPromise, visibleConditionsPromise]);
    };

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
                'The form "' + this.formKey + '" handled by this FormStore cannot handle types'
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
        const {data, rawSchema} = this;
        if (!(tagName in this.pathsByTag)) {
            this.pathsByTag[tagName] = collectTagPaths(tagName, data, rawSchema);
        }

        return this.pathsByTag[tagName];
    }

    getSchemaEntryByPath(schemaPath: string): SchemaEntry {
        return jsonpointer.get(this.schema, schemaPath);
    }
}
