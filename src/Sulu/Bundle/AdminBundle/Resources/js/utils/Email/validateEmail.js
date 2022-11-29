// @flow
export default function(value: ?string): boolean {
    if (!value) {
        return false;
    }

    return /^(.+)@(.+)$/.test(value);
}
