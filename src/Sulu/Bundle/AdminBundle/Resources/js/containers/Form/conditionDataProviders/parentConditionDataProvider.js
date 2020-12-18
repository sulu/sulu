// @flow
import {isObservableArray} from 'mobx';
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
        const evaluatedData = jsonpointer.get(data, parentDataPath);

        if (Array.isArray(evaluatedData) || isObservableArray(evaluatedData)) {
            continue;
        }

        currentConditionData.__parent = {...evaluatedData};
        currentConditionData = currentConditionData.__parent;
    } while (parentDataPath.match(/^\/.*\//));

    return conditionData;
}
