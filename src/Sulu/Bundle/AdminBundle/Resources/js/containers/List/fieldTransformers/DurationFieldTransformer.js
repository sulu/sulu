// @flow
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

/**
 * @internal
 */
export default class DurationFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (value === undefined || value === null || value === '') {
            return null;
        }

        return (Number(value) / 1000).toFixed(1) + 's';
    }
}
