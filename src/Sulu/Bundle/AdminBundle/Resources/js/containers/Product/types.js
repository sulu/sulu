// @flow

export type Entry = {
    id: string,
    required: boolean,
    variantSpecific: boolean,
};

// A column of the attribute table: "name" is the entry key, "type" a registered field type.
export type FieldColumn = {
    name: string,
    title: string,
    type: string,
};

export type Group = {
    entries: Array<{entry: Entry, item: Object}>,
    id: string,
    subtitle?: string,
    title: string,
};
