// @flow
import type {Node} from 'react';

export type Hotspot = {
    _id?: string,
    hotspot: {
        type: string,
    },
    type: string,
};

export type Value = {
    hotspots: Array<Hotspot>,
    imageId: ?number,
};

export type RenderHotspotFormCallback = (value: *, type: string, index: number) => Node;
