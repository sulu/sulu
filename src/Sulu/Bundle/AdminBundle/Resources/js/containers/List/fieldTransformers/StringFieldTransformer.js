// @flow
import React from 'react';
import stringFieldTransformerStyles from './stringFieldTransformer.scss';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

export default class StringFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (value == null || value === '') {
            return null;
        }

        return <span className={stringFieldTransformerStyles.textBox} title={value}>{value}</span>;
    }
}
