// @flow
import moment from 'moment';
import type {FieldTransformer} from '../types';

export default class DateFieldTransformer implements FieldTransformer {
    transform(value: *): * {
        if (!value) {
            return;
        }

        const momentObject = moment(value, moment.ISO_8601);

        return momentObject.format('L');
    }
}
