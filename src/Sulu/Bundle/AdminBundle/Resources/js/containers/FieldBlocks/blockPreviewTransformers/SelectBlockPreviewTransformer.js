// @flow
import React from 'react';
import type {Node} from 'react';
import {isObservableArray} from 'mobx';
import type {BlockPreviewTransformer} from '../types';
import type {SchemaEntry} from '../../Form/types';

export default class SelectBlockPreviewTransformer implements BlockPreviewTransformer {
    transform(value: *, schema: SchemaEntry): Node {
        if (!Array.isArray(value) && !isObservableArray(value)) {
            return null;
        }

        if (!schema.options || !schema.options.values) {
            throw new Error('The "Select" field type must have a "values" schema option!');
        }

        const values = schema.options.values.value;
        if (!Array.isArray(values)) {
            throw new Error('The "SingleSelect" field type must have a "values" option defined being an array!');
        }

        const selectedValues = values.filter((option) => value.includes(option.name));

        if (!selectedValues) {
            return null;
        }

        return <p>{selectedValues.map((selectedValue) => selectedValue.title).join(', ')}</p>;
    }
}
