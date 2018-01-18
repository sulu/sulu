// @flow
import type {Size} from '../../components/Grid';

export type SchemaEntry = {
    label: string,
    type: string,
    size?: Size,
    spaceAfter?: Size,
    items?: Schema,
};

export type Schema = {
    [string]: SchemaEntry,
};
