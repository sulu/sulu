// @flow
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

export default class BytesFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (value === undefined) {
            return undefined;
        }

        if (value === 0) {
            return '0 Byte';
        }

        const k = 1000;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        const i = Math.floor(Math.log(value) / Math.log(k));

        return (value / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    }
}
