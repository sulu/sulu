// @flow
import type {Node} from 'react';
import type {SchemaEntry} from '../Form/types';

export interface BlockPreviewTransformer {
    transform(value: *, schema: SchemaEntry): Node,
}
