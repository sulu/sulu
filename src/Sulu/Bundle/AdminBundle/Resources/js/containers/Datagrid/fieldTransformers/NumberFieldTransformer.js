// @flow
import type {Node} from 'react';
import log from 'loglevel';
import type {FieldTransformer} from '../types';

export default class NumberFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (!value) {
            return null;
        }

        if (isNaN(value)) {
            log.error('Invalid number given: "' + value + '"');

            return null;
        }

        return value.toLocaleString();
    }
}
