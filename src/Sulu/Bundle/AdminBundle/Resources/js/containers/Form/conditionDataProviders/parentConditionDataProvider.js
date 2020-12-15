// @flow
import jsonpointer from 'json-pointer';

export default function(data: Object, dataPath: ?string): {[string]: any} {
    if (!dataPath) {
        return {__parent: data};
    }

    return {__parent: jsonpointer.get(data, dataPath.substring(0, dataPath.lastIndexOf('/')))};
}
