// @flow
import moment from 'moment';
import log from 'loglevel';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

export default class DateFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (!value) {
            return;
        }

        const momentObject = moment(value, moment.ISO_8601);

        if (!momentObject.isValid()) {
            log.error('Invalid date given: "' + value + '". Format needs to be in "ISO 8601"');

            return;
        }

        return momentObject.format('L');
    }
}
