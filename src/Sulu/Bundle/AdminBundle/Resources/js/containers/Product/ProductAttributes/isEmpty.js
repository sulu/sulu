// @flow
export default function isEmpty(value: mixed): boolean {
    return value === undefined || value === null || value === '';
}
