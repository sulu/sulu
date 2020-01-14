// @flow
import React from 'react';
import type {Node} from 'react';
import type {BlockPreviewTransformer} from '../types';
import stringBlockPreviewTransformerStyles from './stringBlockPreviewTransformer.scss';

export default class StringBlockPreviewTransformer implements BlockPreviewTransformer {
    transform(value: *): Node {
        if (typeof value !== 'string') {
            return null;
        }

        return <p className={stringBlockPreviewTransformerStyles.string}>{value}</p>;
    }
}
