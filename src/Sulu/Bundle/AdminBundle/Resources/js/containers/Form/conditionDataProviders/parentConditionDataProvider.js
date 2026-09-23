// @flow
import {isArrayLike} from 'mobx';
import jsonpointer from 'json-pointer';

export default function(data: Object, dataPath: ?string): {[string]: any} {
    if (!dataPath) {
        return {__parent: data};
    }

    let parentDataPath = dataPath;
    const conditionData = {};
    let currentConditionData = conditionData;

    do {
        parentDataPath = parentDataPath.substring(0, parentDataPath.lastIndexOf('/'));

        if (!jsonpointer.has(data, parentDataPath)) {
            currentConditionData.__parent = null;
            break;
        }

        const evaluatedData = jsonpointer.get(data, parentDataPath);

        if (isArrayLike(evaluatedData)) {
            continue;
        }

        currentConditionData.__parent = {...evaluatedData};
        currentConditionData = currentConditionData.__parent;
    } while (parentDataPath.match(/^\/.*\//));

    return conditionData;
}
