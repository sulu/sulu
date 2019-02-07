// @flow
import type {Node} from 'react';

export type BlockEntry = {
    __id?: number,
    type: string,
};

export type RenderBlockContentCallback = (value: *, type: string, index: number) => Node;
