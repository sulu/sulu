// @flow
export default function(value: ?string): ?Date {
    if (!value || typeof value !== 'string') {
        return undefined;
    }

    if (value.match(/^\d\d\d\d-\d\d-\d\d$/)) {
        // The time is necessary to avoid timezone issues
        const date = new Date(value + ' 00:00');

        return date.toString() === 'Invalid Date' ? undefined : date;
    }

    if (value.match(/^\d\d\d\d-\d\d-\d\d \d\d:\d\d$/)) {
        const date = new Date(value);

        return date.toString() === 'Invalid Date' ? undefined : date;
    }

    return undefined;
}
