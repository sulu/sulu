// @flow
import moment from 'moment';
import log from 'loglevel';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

const format = 'HH:mm:ss';

export default class TimeFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (!value) {
            return null;
        }

        const momentObject = moment(value, format);

        if (!momentObject.isValid()) {
            log.error('Invalid time given: "' + value + '". Format needs to be "' + format + '"');

            return null;
        }

        return momentObject.format('LT');
    }
}
