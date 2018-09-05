// @flow

export default function buildQueryString(queryOptions: ?Object) {
    const options = queryOptions;
    if (!options) {
        return '';
    }

    if (Object.values(options).every((option) => option === undefined)) {
        return '';
    }

    const searchParameters = new URLSearchParams();
    Object.keys(options).forEach((key) => {
        if (options[key] === undefined) {
            return;
        }

        searchParameters.set(key, options[key]);
    });

    return '?' + searchParameters.toString().replace(/%2C/gi, ',');
}
