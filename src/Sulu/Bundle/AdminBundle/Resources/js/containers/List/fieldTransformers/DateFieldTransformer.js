// @flow
import moment from 'moment';
import log from 'loglevel';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

const format = 'YYYY-MM-DD';

export default class DateFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (!value) {
            return null;
        }

        const momentObject = moment(value, format);

        if (!momentObject.isValid()) {
            log.error('Invalid date given: "' + value + '". Format needs to be "' + format + '"');

            return null;
        }

        return momentObject.format('L');
    }
}
