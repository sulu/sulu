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

export default class SelectBlockPreviewTransformer implements BlockPreviewTransformer {
    transform(value: *, schema: SchemaEntry): Node {
        if (!Array.isArray(value) && !isArrayLike(value)) {
            return null;
        }

        if (!schema.options || !schema.options.values) {
            throw new Error('The "Select" field type must have a "values" schema option!');
        }

        const values = schema.options.values.value;
        if (!Array.isArray(values) && !isArrayLike(values)) {
            throw new Error('The "SingleSelect" field type must have a "values" option defined being an array!');
        }

        const normalizedValue = Array.isArray(value) ? value : Array.from((value: any));
        const normalizedValues = Array.isArray(values) ? values : Array.from((values: any));
        const selectedValues = normalizedValues.filter((option) => normalizedValue.includes(option.name));

        if (!selectedValues) {
            return null;
        }

        return <p>{selectedValues.map((selectedValue) => selectedValue.title).join(', ')}</p>;
    }
}
