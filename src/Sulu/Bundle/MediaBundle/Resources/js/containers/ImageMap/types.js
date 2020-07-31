// @flow
export type Hotspot = {
    formData: Object,
    formType: string,
    selection: Object | typeof undefined,
    type: string,
};

export type Value = {
    hotspots: Array<Hotspot>,
    imageId: ?number,
};
