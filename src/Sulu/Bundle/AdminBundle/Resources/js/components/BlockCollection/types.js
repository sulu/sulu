// @flow
import type {Node} from 'react';

export type RenderBlockContentCallback<T: string, U: {type: T}>
    = (value: U, type: T, index: number, expanded: boolean) => Node;
