// @flow
import type {Node} from 'react';
import type {SchemaEntry} from '../Form/types';

export type BlockEntry = {
    settings?: {[string]: any},
    type: string,
};

export type BlockPreviewTransformerMap = {[string]: BlockPreviewTransformer};

export interface BlockPreviewTransformer {
    transform(value: *, schema: SchemaEntry): Node,
}
