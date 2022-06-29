// @flow
import React from 'react';
import type {Node} from 'react';
import colorFieldTransformerStyles from './stringFieldTransformer.scss';
import type {FieldTransformer} from '../types';

export default class StringFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        return <span title={value} className={colorFieldTransformerStyles.textBox}>{value}</span>;
    }
}
