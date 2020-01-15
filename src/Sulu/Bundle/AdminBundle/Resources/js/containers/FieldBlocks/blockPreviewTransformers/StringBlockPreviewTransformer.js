// @flow
import React from 'react';
import type {Node} from 'react';
import type {BlockPreviewTransformer} from '../types';

const MAX_LENGTH = 50;

export default class StringBlockPreviewTransformer implements BlockPreviewTransformer {
    transform(value: *): Node {
        if (typeof value !== 'string') {
            return null;
        }

        return <p>{value.length > MAX_LENGTH ? value.substring(0, MAX_LENGTH) + '...' : value}</p>;
    }
}
