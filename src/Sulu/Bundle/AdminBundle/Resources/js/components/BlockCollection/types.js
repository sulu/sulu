// @flow
import type {Node} from 'react';

export type BlockEntry<T> = {
    settings?: {[string]: any},
    type: T,
};

export type RenderBlockContentCallback = (value: *, type: string, index: number, expanded: boolean) => Node;
