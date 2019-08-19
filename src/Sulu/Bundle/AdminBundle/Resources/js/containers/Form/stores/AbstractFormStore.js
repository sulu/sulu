// @flow
import { action, computed, observable, set, toJS, untracked, when } from 'mobx';
import type { IObservableValue } from 'mobx';
import jexl from 'jexl';
import jsonpointer from 'json-pointer';
import log from 'loglevel';
import type { RawSchema, RawSchemaEntry, Schema, SchemaEntry } from '../types';

const SECTION_TYPE = 'section';

function addSchemaProperties(data: Object, key: string, schema: RawSchema) {
    const type = schema[key].type;

    if (type !== SECTION_TYPE) {
        jsonpointer.set(data, '/' + key, undefined);
    }

    const items = schema[key].items;

    if (type === SECTION_TYPE && items) {
        Object.keys(items)
            .reduce((object, childKey) => addSchemaProperties(data, childKey, items), data);
    }

    return data;
}

function transformRawSchema(
    rawSchema: RawSchema,
    data: Object,
    locale: ?string,
    basePath: string = ''
): Schema {
    return Object.keys(rawSchema).reduce((schema, schemaKey) => {
        schema[schemaKey] = transformRawSchemaEntry(
            rawSchema[schemaKey],
            data,
            locale,
            basePath + '/' + schemaKey
        );

        return schema;
    }, {});
}

function transformRawSchemaEntry(
    rawSchemaEntry: RawSchemaEntry,
    data: Object,
    locale: ?string,
    path: string
): SchemaEntry {
    const evaluationData = {...data, __locale: locale};

    return Object.keys(rawSchemaEntry).reduce((schemaEntry, schemaEntryKey) => {
        if (schemaEntryKey === 'disabledCondition' && rawSchemaEntry[schemaEntryKey]) {
            schemaEntry.disabled = jexl.evalSync(rawSchemaEntry[schemaEntryKey], evaluationData);
        } else if (schemaEntryKey === 'visibleCondition' && rawSchemaEntry[schemaEntryKey]) {
            schemaEntry.visible = jexl.evalSync(rawSchemaEntry[schemaEntryKey], evaluationData);
        } else if (schemaEntryKey === 'mandatoryCondition' && rawSchemaEntry[schemaEntryKey]) {
            schemaEntry.required = jexl.evalSync(rawSchemaEntry[schemaEntryKey], evaluationData);
        } else if (schemaEntryKey === 'required') {
            schemaEntry.required = schemaEntry.required === undefined ? rawSchemaEntry[schemaEntryKey] : schemaEntry[schemaEntryKey];
        } else if (schemaEntryKey === 'items' && rawSchemaEntry.items) {
            schemaEntry.items = transformRawSchema(rawSchemaEntry.items, data, path);
        } else if (schemaEntryKey === 'types' && rawSchemaEntry.types) {
            const rawSchemaEntryTypes = rawSchemaEntry.types;

            schemaEntry.types = Object.keys(rawSchemaEntryTypes).reduce((schemaEntryTypes, schemaEntryTypeKey) => {
                schemaEntryTypes[schemaEntryTypeKey] = {
                    title: rawSchemaEntryTypes[schemaEntryTypeKey].title,
                    form: transformRawSchema(
                        rawSchemaEntryTypes[schemaEntryTypeKey].form,
                        data,
                        locale,
                        path + '/types/' + schemaEntryTypeKey + '/form'
                    ),
                };

                return schemaEntryTypes;
            }, {});
        } else {
            // $FlowFixMe
            schemaEntry[schemaEntryKey] = rawSchemaEntry[schemaEntryKey];
        }

        if (schemaEntry['required'] === true && (schemaEntry['disabled'] === true || schemaEntry['visible'] === false)) {
            schemaEntry['required'] = false;
            log.warn("Mandatory field has been disabled or hidden and is no longer mandatory!")
        }

        return schemaEntry;
    }, {});
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

export default class AbstractFormStore {
    +data: Object;
    +loading: boolean;
    +locale: ?IObservableValue<string>;
    @observable rawSchema: RawSchema;
    @observable evaluatedSchema: Schema = {};
    modifiedFields: Array<string> = [];
    @observable errors: Object = {};
    validator: ?(data: Object) => boolean;
    pathsByTag: { [tagName: string]: Array<string> } = {};

    @computed.struct get schema(): Schema {
        return toJS(this.evaluatedSchema);
    }

    isFieldModified(dataPath: string): boolean {
        return this.modifiedFields.includes(dataPath);
    }

    finishField(dataPath: string) {
        if (!this.modifiedFields.includes(dataPath)) {
            this.modifiedFields.push(dataPath);
        }

        this.updateFieldPathEvaluations();
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
            return false;
        }

        return true;
    }

    updateFieldPathEvaluations = () => {
        const {loading, rawSchema} = this;
        const locale = this.locale ? this.locale.get() : undefined;

        when(
            () => !loading,
            (): void => this.setEvaluatedSchema(
                transformRawSchema(
                    rawSchema,
                    untracked(() => toJS(this.data)),
                    locale
                )
            )
        );
    };

    @action setEvaluatedSchema(evaluatedSchema: Schema) {
        this.evaluatedSchema = evaluatedSchema;
    }

    getValueByPath = (path: string): mixed => {
        return jsonpointer.has(this.data, path) ? jsonpointer.get(this.data, path) : undefined;
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

    @action addMissingSchemaProperties() {
        const schemaFields = Object.keys(this.rawSchema)
            .reduce((data, key) => addSchemaProperties(data, key, this.rawSchema), {});
        set(this.data, {...schemaFields, ...this.data});
    }
}
