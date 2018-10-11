// @flow
import type {Size} from '../../components/Grid';

export type SchemaType = {
    key: string,
    title: string,
};

export type SchemaTypes = {[key: string]: SchemaType};

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

type BaseSchemaEntry = {
    description?: string,
    label?: string,
    maxOccurs?: number,
    minOccurs?: number,
    options?: Object,
    required?: boolean,
    size?: Size,
    spaceAfter?: Size,
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
