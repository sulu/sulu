// @flow
import type {Node} from 'react';
import type {SchemaEntry} from '../Form/types';

export type BlockEntry<T> = {
    settings?: {[string]: any},
    type: T,
};

export type BlockPreviewTransformerMap = {[string]: BlockPreviewTransformer};

export interface BlockPreviewTransformer {
    transform(value: *, schema: SchemaEntry): Node,
}
