// @flow

export default function(value: Date) {
    const year = value.getFullYear().toString();
    const month = (value.getMonth() + 1).toString();
    const date = value.getDate().toString();

    return year + '-' + (month[1] ? month : '0' + month) + '-' + (date[1] ? date : '0' + date);
}
