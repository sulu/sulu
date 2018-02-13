// @flow
import type {Size} from '../../components/Grid';

export type SchemaType = {
    key: string,
    title: string,
};

export type SchemaTypes = {[key: string]: SchemaType};

export type Type = {
    title: string,
    form: Schema,
};
export type Types = {[key: string]: Type};

export type SchemaEntry = {
    label: string,
    type: string,
    size?: Size,
    spaceAfter?: Size,
    items?: Schema,
    options?: Object,
    types?: Types,
};

export type Schema = {
    [string]: SchemaEntry,
};
