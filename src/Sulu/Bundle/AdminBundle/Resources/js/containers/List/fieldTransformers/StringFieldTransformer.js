// @flow
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

export default class StringFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        return value;
    }
}
