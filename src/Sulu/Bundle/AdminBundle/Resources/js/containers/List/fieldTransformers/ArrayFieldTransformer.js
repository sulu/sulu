// @flow
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

export default class ArrayFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (!value) {
            return null;
        }

        return value.join(', ');
    }
}
