// @flow
import type {Node} from 'react';
import type {FieldTransformer} from '../types';
import {transformBytesToReadableString} from '../../../utils';

export default class BytesFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (value === undefined) {
            return null;
        }

        return transformBytesToReadableString(value);
    }
}
