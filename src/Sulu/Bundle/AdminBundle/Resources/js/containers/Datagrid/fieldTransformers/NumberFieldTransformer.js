// @flow
import type {Node} from 'react';
import type {FieldTransformer} from '../types';
import userStore from '../../../stores/UserStore/UserStore';

export default class NumberFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        const float = parseFloat(value);

        if (isNaN(float)) {
            return null;
        }

        return float.toLocaleString(userStore.user ? userStore.user.locale : undefined);
    }
}
