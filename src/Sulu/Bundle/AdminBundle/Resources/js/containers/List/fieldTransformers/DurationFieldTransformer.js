// @flow
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

/**
 * @experimental We can not yet give BC Promise for this new component in Sulu 2.6.
 */
export default class DurationFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (value === undefined || value === null || value === '') {
            return null;
        }

        const milliseconds = Number(value);

        if (milliseconds < 1000) {
            return Math.round(milliseconds).toLocaleString('en') + 'ms';
        }

        const totalSeconds = milliseconds / 1000;

        if (totalSeconds < 60) {
            return totalSeconds.toLocaleString('en', {minimumFractionDigits: 1, maximumFractionDigits: 1}) + 's';
        }

        const totalMinutes = Math.floor(totalSeconds / 60);
        const remainingSeconds = Math.round(totalSeconds % 60);

        if (totalMinutes < 60) {
            return remainingSeconds > 0
                ? totalMinutes.toLocaleString('en') + 'm ' + remainingSeconds.toLocaleString('en') + 's'
                : totalMinutes.toLocaleString('en') + 'm';
        }

        const hours = Math.floor(totalMinutes / 60);
        const remainingMinutes = totalMinutes % 60;

        return remainingMinutes > 0
            ? hours.toLocaleString('en') + 'h ' + remainingMinutes.toLocaleString('en') + 'm'
            : hours.toLocaleString('en') + 'h';
    }
}
