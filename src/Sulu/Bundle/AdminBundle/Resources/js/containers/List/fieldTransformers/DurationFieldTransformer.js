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

        const milliseconds = Math.round(Number(value));

        if (milliseconds < 1000) {
            return milliseconds.toLocaleString() + 'ms';
        }

        // Seconds are rounded to a whole number before deciding the unit, so a value like
        // 59999ms (59.999s) is treated as 60s and promoted to "1m" instead of rendered as "60.0s".
        const totalSeconds = Math.round(milliseconds / 1000);

        if (totalSeconds < 60) {
            const seconds = milliseconds / 1000;

            return seconds.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1}) + 's';
        }

        const totalMinutes = Math.floor(totalSeconds / 60);
        const remainingSeconds = totalSeconds % 60;

        if (totalMinutes < 60) {
            return remainingSeconds > 0
                ? totalMinutes.toLocaleString() + 'm ' + remainingSeconds.toLocaleString() + 's'
                : totalMinutes.toLocaleString() + 'm';
        }

        const hours = Math.floor(totalMinutes / 60);
        const remainingMinutes = totalMinutes % 60;

        return remainingMinutes > 0
            ? hours.toLocaleString() + 'h ' + remainingMinutes.toLocaleString() + 'm'
            : hours.toLocaleString() + 'h';
    }
}
