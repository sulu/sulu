// @flow
import {action, autorun, computed, get, isArrayLike, observable, toJS, when} from 'mobx';
import jsonpointer from 'json-pointer';
import {createAjv} from '../../../utils/Ajv';
import ResourceStore from '../../../stores/ResourceStore';
import AbstractFormStore, {SECTION_TYPE} from './AbstractFormStore';
import metadataStore from './metadataStore';
import type {FormStoreInterface, Schema, SchemaEntry, SchemaType, SchemaTypes} from '../types';
import type {IObservableValue} from 'mobx/lib/mobx';

// TODO do not hardcode "template", use some kind of metadata instead
const TYPE = 'template';

const ajv = createAjv();

export default class ResourceFormStore extends AbstractFormStore implements FormStoreInterface {
    resourceStore: ResourceStore;
    formKey: string;
    options: { [string]: any };
    @observable type: string;
    @observable types: { [key: string]: SchemaType } = {};
    @observable schemaLoading: boolean = true;
    @observable typesLoading: boolean = true;
    schemaDisposer: ?() => void;
    typeDisposer: ?() => void;
    metadataOptions: ?{ [string]: any };

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

    handleSchemaResponse = ([schema, jsonSchema]: [Schema, Object]) => {
        this.validator = jsonSchema ? ajv.compile(jsonSchema) : undefined;
        this.pathsByTag = {};

        return this.loadAndMergeRemoteData(this.schema, schema).then(action(() => {
            this.schema = schema;
            this.addMissingSchemaProperties();
            this.validate();
            this.setSchemaLoading(false);
        }));
    };

    loadAndMergeRemoteData = (localSchema: Schema, remoteSchema: Schema) => {
        // load data only after initial schema was set to prevent duplicate requests during initialization
        if (localSchema) {
            return this.resourceStore.requestRemoteData({template: this.type}).then((data: Object) => {
                const result = this.mergeData(localSchema, remoteSchema, this.data, data);
                this.setMultiple(result);
            });
        }
        return Promise.resolve();
    };

    mergeData(
        localSchema: Schema,
        remoteSchema: Schema,
        localData: Object,
        remoteData: Object
    ) {
        let result = {};
        if (!localSchema || !remoteSchema) {
            return result;
        }

        for (const name in remoteSchema) {
            const {
                items: remoteItems,
                defaultType: remoteDefaultType,
                type: remoteType,
                types: remoteTypes,
            } = remoteSchema[name];
            const {
                items: localItems,
                defaultType: localDefaultType,
                type: localType,
                types: localTypes,
            } = localSchema[name] || {};

            if (remoteType === SECTION_TYPE && remoteItems &&
                localType === SECTION_TYPE && localItems) {
                result = this.mergeData(
                    localItems,
                    remoteItems,
                    localData,
                    remoteData
                );
                continue;
            }

            if (remoteType === SECTION_TYPE && remoteItems) {
                result = this.mergeData(
                    localSchema,
                    remoteItems,
                    localData,
                    remoteData
                );
                continue;
            }

            if (localType === SECTION_TYPE && localItems) {
                result = this.mergeData(
                    localItems,
                    remoteSchema,
                    localData,
                    remoteData
                );
                continue;
            }
            if (remoteTypes && localTypes
                && Object.keys(remoteTypes).length > 0 && Object.keys(localTypes).length > 0
                && localData[name] && remoteData[name]
                && isArrayLike(localData[name]) && isArrayLike(remoteData[name])
            ) {
                for (let key = 0; key < Math.max(remoteData[name].length, localData[name].length); ++key) {
                    const remoteChildData = toJS(remoteData[name].length > key ? remoteData[name][key] || {} : {});
                    const localChildData = toJS(localData[name].length > key ? localData[name][key] || {} : {});

                    const localChildSchema =
                        // $FlowFixMe
                        localTypes[localChildData.type]?.form || localTypes[localDefaultType].form;

                    const remoteChildSchema =
                        // $FlowFixMe
                        remoteTypes[remoteChildData.type]?.form || remoteTypes[remoteDefaultType].form;

                    const resultChildData = this.mergeData(
                        localChildSchema,
                        remoteChildSchema,
                        localChildData,
                        remoteChildData
                    );

                    if (!result[name]) {
                        result[name] = [];
                    }

                    if (Object.keys(resultChildData).length > 0) {
                        if (!resultChildData?.type) {
                            const {
                                defaultType: remoteChildDefaultType,
                                types: remoteChildTypes,
                            } = remoteChildSchema;
                            const localChildDataType = localChildData?.type;

                            resultChildData.type = localChildDataType && remoteChildTypes &&
                            localChildDataType in remoteChildTypes ?
                                localChildData.type :
                                remoteChildData?.type || remoteChildDefaultType;
                        }
                        if (resultChildData.settings) {
                            resultChildData.settings = localChildData?.settings || remoteChildData.settings;
                        }

                        result[name][key] = resultChildData;
                    }
                }

                continue;
            }

            if (!localData[name] && remoteData[name]) {
                result[name] = remoteData[name];
            }

            if (localData[name] && remoteSchema[name].type !== localSchema[name]?.type) {
                result[name] = undefined;
            }
        }

        return result;
    }

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
