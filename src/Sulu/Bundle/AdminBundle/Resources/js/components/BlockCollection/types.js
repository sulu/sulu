// @flow
import type {Node} from 'react';

export type BlockEntry = {
    type?: string,
};

export type RenderBlockContentCallback = (value: *, type: ?string, index: number) => Node;
