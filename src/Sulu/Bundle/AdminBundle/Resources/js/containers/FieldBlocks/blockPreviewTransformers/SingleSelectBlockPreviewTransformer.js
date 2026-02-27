// @flow
import React from 'react';

import type {Node} from 'react';
import type {BlockPreviewTransformer} from '../types';
import type {SchemaEntry} from '../../Form/types';

function isArrayLike(value: mixed): boolean {
    return !!value
        && typeof value === 'object'
        && typeof value.length === 'number'
        && typeof value.forEach === 'function';
}

export default class SingleSelectBlockPreviewTransformer implements BlockPreviewTransformer {
    transform(value: *, schema: SchemaEntry): Node {
        if (!schema.options || !schema.options.values) {
            throw new Error('The "SingleSelect" field type must have a "values" schema option!');
        }

        const values = schema.options.values.value;
        if (!Array.isArray(values) && !isArrayLike(values)) {
            throw new Error('The "SingleSelect" field type must have a "values" option defined being an array!');
        }

        const normalizedValues = Array.isArray(values) ? values : Array.from((values: any));
        const selectedValue = normalizedValues.find((option) => option.name === value);

        if (!selectedValue) {
            return null;
        }

        return <p>{selectedValue.title}</p>;
    }
}
