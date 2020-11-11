// @flow
const dateTimeFormat = new Intl.DateTimeFormat(
    'en',
    {hour: 'numeric', minute: 'numeric', second: 'numeric', hour12: false}
);

export default function(date: ?Date) {
    if (!date) {
        return undefined;
    }

    return dateTimeFormat.format(date);
}
