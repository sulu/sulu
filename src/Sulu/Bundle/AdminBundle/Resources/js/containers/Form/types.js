// @flow
export type SchemaEntry = {
    label: string,
    type: string,
};

export type Schema = {
    [string]: SchemaEntry,
};
