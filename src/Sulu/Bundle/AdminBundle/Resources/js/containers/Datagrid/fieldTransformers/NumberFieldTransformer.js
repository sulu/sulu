// @flow
import type {Node} from 'react';
import log from 'loglevel';
import type {FieldTransformer} from '../types';
import userStore from '../../../stores/UserStore';

export default class NumberFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (!value) {
            return null;
        }

        const float = parseFloat(value);

        if (isNaN(float)) {
            log.error('Invalid number given: "' + value + '"');

            return null;
        }

        return float.toLocaleString(userStore.user ? userStore.user.locale : undefined);
    }
}
