// @flow
import React from 'react';
import type {Node} from 'react';
import type {BlockPreviewTransformer} from '../types';

function isNumber(value: mixed): boolean %checks {
    return typeof value === 'number';
}

export default class NumberRangeBlockPreviewTransformer implements BlockPreviewTransformer {
    transform(value: *): Node {
        if (!value || typeof value !== 'object') {
            return null;
        }

        const {from, to} = value;
        if (!isNumber(from) && !isNumber(to)) {
            return null;
        }

        return <p>{(isNumber(from) ? String(from) : '') + ' – ' + (isNumber(to) ? String(to) : '')}</p>;
    }
}
