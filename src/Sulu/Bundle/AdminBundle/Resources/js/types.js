// @flow
import {FormInspector} from './containers/Form';
import type {Types} from './containers/Form';

export type PropertyError = {
    keyword: string,
    parameters: {[key: string]: mixed},
};

export type BlockError = Array<?{[key: string]: Error}>;

export type Error = BlockError | PropertyError;

export type ErrorCollection = {[key: string]: Error};

export type SchemaOption = {
    name?: string | number,
    infoText?: string,
    title?: string,
    value?: ?string | number | Array<SchemaOption>,
};

export type SchemaOptions = {[key: string]: SchemaOption};

export type FieldTypeProps<T> = {|
    dataPath: string,
    disabled: ?boolean,
    error: ?Error | ErrorCollection,
    fieldTypeOptions: Object,
    formInspector: FormInspector,
    label: string,
    maxOccurs: ?number,
    minOccurs: ?number,
    onChange: (value: T) => void,
    onFinish: (subDataPath: ?string, subSchemaPath: ?string) => void,
    schemaOptions?: SchemaOptions,
    schemaPath: string,
    showAllErrors: boolean,
    types: ?Types,
    value: ?T,
|};
