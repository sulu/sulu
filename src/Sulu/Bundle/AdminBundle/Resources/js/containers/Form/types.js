// @flow
import Router from '../../services/Router';
import FormInspector from './FormInspector';
import type {IObservableValue} from 'mobx';
import type {ColSpan} from '../../components/Grid';

export type SchemaType = {
    key: string,
    title: string,
};

export type SchemaTypes = {
    defaultType: ?string,
    types: {[key: string]: SchemaType},
};

export type Tag = {
    name: string,
    priority?: number,
};

type BaseType = {
    title: string,
};

export type RawType = BaseType & {
    form: RawSchema,
};

export type RawTypes = {[key: string]: RawType};

export type Type = BaseType & {
    form: Schema,
};

export type Types = {[key: string]: Type};

export type PropertyError = {
    keyword: string,
    parameters: {[key: string]: mixed},
};

export type BlockError = Array<?{[key: string]: Error}>;

export type Error = BlockError | PropertyError;

export type ErrorCollection = {[key: string]: Error};

export type SchemaOption = {
    infoText?: string,
    name: string | number,
    title?: string,
    value?: ?string | number | boolean | Array<SchemaOption>,
};

export type SchemaOptions = {[key: string]: SchemaOption | typeof undefined};

type BaseSchemaEntry = {
    colSpan?: ColSpan,
    defaultType?: string,
    description?: string,
    label?: string,
    maxOccurs?: number,
    minOccurs?: number,
    onInvalid?: string,
    options?: SchemaOptions,
    required?: boolean,
    spaceAfter?: ColSpan,
    tags?: Array<Tag>,
    type: string,
};

export type RawSchemaEntry = BaseSchemaEntry & {
    disabledCondition?: string,
    items?: RawSchema,
    types?: RawTypes,
    visibleCondition?: string,
};

export type SchemaEntry = BaseSchemaEntry & {
    disabled?: boolean,
    items?: Schema,
    types?: Types,
    visible?: boolean,
};

export type RawSchema = {[string]: RawSchemaEntry};

export type Schema = {[string]: SchemaEntry};

export type FinishFieldHandler = (dataPath: string, schemaPath: string) => void;

export type SaveHandler = (action: ?string | {[string]: any}) => void;

export type ConditionDataProvider = (
    data: {[string]: any},
    options: {[string]: any},
    metadataOptions: ?{[string]: any}
) => {[string]: any};

export interface FormStoreInterface {
    +change: (name: string, value: mixed) => void,
    // Only exists in one implementation, therefore optional. Maybe we can remove that definition one day...
    +copyFromLocale?: (string) => Promise<*>,
    +data: {[string]: any},
    +destroy: () => void,
    dirty: boolean,
    +errors: Object,
    +finishField: (dataPath: string) => void,
    +forbidden: boolean,
    +getPathsByTag: (tagName: string) => Array<string>,
    +getSchemaEntryByPath: (schemaPath: string) => ?SchemaEntry,
    +getValueByPath: (path: string) => mixed,
    +getValuesByTag: (tagName: string) => Array<mixed>,
    +hasInvalidType: boolean,
    +id: ?string | number,
    +isFieldModified: (dataPath: string) => boolean,
    +loading: boolean,
    +locale: ?IObservableValue<string>,
    +metadataOptions: ?{[string]: any},
    +options: SchemaOptions,
    +resourceKey: ?string,
    +schema: Object,
    +set: (name: string, value: mixed) => void,
    +setMultiple: (data: Object) => void,
    +setType: (type: string) => void,
    +types: {[key: string]: SchemaType},
    +validate: () => boolean,
}

export type FieldChangeContext = {
    isDefaultValue?: boolean,
};

export type FieldTypeProps<T> = {|
    dataPath: string,
    defaultType: ?string,
    disabled: ?boolean,
    error: ?Error | ErrorCollection,
    fieldTypeOptions: Object,
    formInspector: FormInspector,
    label: ?string,
    maxOccurs: ?number,
    minOccurs: ?number,
    onChange: (value: T, context?: FieldChangeContext) => void,
    onFinish: (subDataPath: ?string, subSchemaPath: ?string) => void,
    onSuccess: ?() => void,
    router: ?Router,
    schemaOptions: SchemaOptions,
    schemaPath: string,
    showAllErrors: boolean,
    types: ?Types,
    value: ?T,
|};
