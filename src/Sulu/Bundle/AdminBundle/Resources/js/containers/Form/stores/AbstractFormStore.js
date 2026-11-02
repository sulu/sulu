// @flow
import {action, computed, isArrayLike, observable, set, toJS} from 'mobx';
import jsonpointer from 'json-pointer';
import log from 'loglevel';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {Schema, SchemaEntry} from '../types';

export const SECTION_TYPE = 'section';

function deepMerge(base: Object, override: Object): Object {
    const result = {...base};
    for (const key of Object.keys(override)) {
        const overrideVal = override[key];
        const baseVal = result[key];

        if (
            overrideVal !== null
            && typeof overrideVal === 'object'
            && !Array.isArray(overrideVal)
            && baseVal !== null
            && typeof baseVal === 'object'
            && !Array.isArray(baseVal)
        ) {
            result[key] = deepMerge(baseVal, overrideVal);
        } else {
            result[key] = overrideVal;
        }
    }
    return result;
}

// json-pointer creates an array when the next segment is numeric; the schema always expects an object
function setNestedProperty(data: Object, key: string, value: mixed) {
    const tokens = key.split('/');
    let current = data;

    for (let i = 0; i < tokens.length - 1; i++) {
        const token = tokens[i];

        if (token === '__proto__' || token === 'constructor' || token === 'prototype') {
            return;
        }

        if (typeof current[token] !== 'object' || current[token] === null) {
            current[token] = {};
        }

        current = current[token];
    }

    current[tokens[tokens.length - 1]] = value;
}

function addSchemaProperties(data: Object, key: string, schema: Schema) {
    const type = schema[key].type;

    if (type !== SECTION_TYPE) {
        setNestedProperty(data, key, undefined);
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

        if (types
            && Object.keys(types).length > 0
            && data[key]
            && (isArrayLike(data[key]))
        ) {
            for (const childKey of data[key].keys()) {
                const childData = data[key][childKey];

                if (childData.type in types) {
                    pathsWithPriority.push(
                        ...collectTagPathsWithPriority(
                            tagName,
                            childData,
                            types[childData.type].form,
                            parentPath.concat([key, childKey])
                        )
                    );
                }
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

export default class AbstractFormStore
{
    +data: {[string]: any};
    +options: {[string]: any};
    +metadataOptions: ?{[string]: any};
    +loading: boolean;
    +locale: ?IObservableValue<string>;
    @observable schema: Schema;
    modifiedFields: Array<string> = [];
    @observable schemaErrors: Object = {};
    // keyed by the data path of the field, e.g. "/blocks/0/key"
    @observable serverErrors: {[dataPath: string]: Array<string>} = {};
    validator: ?(data: Object) => boolean;
    pathsByTag: {[tagName: string]: Array<string>} = {};

    get forbidden(): boolean {
        return false;
    }

    get notFound(): boolean {
        return false;
    }

    get unexpectedError(): boolean {
        return false;
    }

    isFieldModified(dataPath: string): boolean {
        return this.modifiedFields.includes(dataPath);
    }

    finishField(dataPath: string) {
        if (!this.modifiedFields.includes(dataPath)) {
            this.modifiedFields.push(dataPath);
        }
    }

    @action validate() {
        const {validator} = this;
        const errors = {};

        if (validator && !validator(toJS(this.data))) {
            // $FlowFixMe
            for (const error of validator.errors) {
                switch (error.keyword) {
                    case 'type':
                    case 'if':
                    case 'then':
                    case 'else':
                    case 'oneOf':
                    case 'anyOf':
                    case 'allOf':
                        // these errors are not shown in the leaf field, e.g. in blocks and similar constructs
                        // these errors also have child errors, which will be shown on the correct leaf field
                        break;
                    case 'required':
                        jsonpointer.set(
                            errors,
                            error.instancePath + '/' + error.params.missingProperty,
                            {keyword: error.keyword, parameters: error.params}
                        );
                        break;
                    case 'not':
                        // the failing subschema is kept so that the field can tell different "not" constraints apart
                        jsonpointer.set(
                            errors,
                            error.instancePath,
                            {keyword: error.keyword, parameters: error.params, schema: error.schema}
                        );
                        break;
                    default:
                        jsonpointer.set(
                            errors,
                            error.instancePath,
                            {keyword: error.keyword, parameters: error.params}
                        );
                }
            }
        }

        this.schemaErrors = errors;

        if (Object.keys(errors).length > 0) {
            log.info('Form validation detected the following errors: ', toJS(errors));
            return false;
        }

        return true;
    }

    // Errors the server named for a field are merged into the ones of the schema, but do not take part in validate():
    // the server decides again on the next save, so one that went stale must not block the request.
    @computed get errors(): Object {
        const errors = toJS(this.schemaErrors);

        Object.keys(this.serverErrors).forEach((dataPath) => {
            jsonpointer.set(errors, dataPath, {messages: toJS(this.serverErrors[dataPath])});
        });

        return errors;
    }

    @computed get hasErrors() {
        return Object.keys(this.errors).length > 0;
    }

    @action setServerErrors(fieldErrors: Array<{path: string, title: string}>) {
        const serverErrors = {};

        fieldErrors.forEach(({path, title}) => {
            // an error without a path belongs to the whole form and has no field to be shown at
            if (!path) {
                return;
            }

            const dataPath = path.startsWith('/') ? path : '/' + path;
            serverErrors[dataPath] = [...(serverErrors[dataPath] || []), title];
        });

        this.serverErrors = serverErrors;
    }

    /**
     * Removes the errors of the server for a field that was changed, or all of them without a data path.
     * A change inside of a block also removes the errors of the block, and the other way around.
     */
    @action clearServerErrors(dataPath?: string) {
        if (Object.keys(this.serverErrors).length === 0) {
            return;
        }

        if (dataPath === undefined) {
            this.serverErrors = {};

            return;
        }

        const sanitizedDataPath = dataPath.startsWith('/') ? dataPath : '/' + dataPath;
        const serverErrors = {};

        Object.keys(this.serverErrors).forEach((errorPath) => {
            const related = errorPath === sanitizedDataPath
                || errorPath.startsWith(sanitizedDataPath + '/')
                || sanitizedDataPath.startsWith(errorPath + '/');

            if (!related) {
                serverErrors[errorPath] = this.serverErrors[errorPath];
            }
        });

        this.serverErrors = serverErrors;
    }

    getValueByPath = (dataPath: string): mixed => {
        return jsonpointer.has(this.data, dataPath) ? jsonpointer.get(this.data, dataPath) : undefined;
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

    getSchemaEntryByPath(schemaPath: string): ?SchemaEntry {
        return jsonpointer.get(this.schema, schemaPath);
    }

    @action addMissingSchemaProperties() {
        const schemaFields = Object.keys(this.schema)
            .reduce((data, key) => addSchemaProperties(data, key, this.schema), {});
        set(this.data, deepMerge(schemaFields, this.data));
    }

    destroy() {}
}
