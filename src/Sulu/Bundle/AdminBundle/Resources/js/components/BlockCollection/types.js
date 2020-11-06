// @flow
import type {Node} from 'react';

export type BlockEntry<T> = {
    settings?: {[string]: any},
    type: T,
};

export type RenderBlockContentCallback<T: string> = (value: *, type: T, index: number, expanded: boolean) => Node;
