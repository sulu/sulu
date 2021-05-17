// @flow
import {transformBytesToReadableString} from '../../../utils';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

export default class BytesFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (value === undefined) {
            return null;
        }

        return transformBytesToReadableString(value);
    }
}
