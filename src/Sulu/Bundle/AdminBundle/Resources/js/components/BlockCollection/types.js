// @flow
import type {Node} from 'react';

export type BlockEntry = {
    settings?: {[string]: any},
    type: string,
};

export type RenderBlockContentCallback = (value: *, type: string, index: number, expanded: boolean) => Node;
