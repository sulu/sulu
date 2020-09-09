// @flow

export default function(value: Date) {
    const year = value.getFullYear().toString();
    const month = (value.getMonth() + 1).toString();
    const date = value.getDate().toString();

    const hour = value.getHours().toString();
    const minute = value.getMinutes().toString();

    const dateString = year + '-' + (month[1] ? month : '0' + month) + '-' + (date[1] ? date : '0' + date);
    const timeString = (hour[1] ? hour : '0' + hour) + ':' + (minute[1] ? minute : '0' + minute);

    return dateString + ' ' + timeString;
}
