// @flow

export type Entry = {
    id: string,
    mode: 'shared' | 'variantSpecific' | 'overridable',
    required: boolean,
};

// A column of the attribute table: "name" is the entry key, "type" a registered field type.
export type FieldColumn = {
    name: string,
    schemaOptions?: Object,
    title: string,
    type: string,
};

export type Group = {
    entries: Array<{entry: Entry, item: Object}>,
    id: string,
    subtitle?: string,
    title: string,
};
