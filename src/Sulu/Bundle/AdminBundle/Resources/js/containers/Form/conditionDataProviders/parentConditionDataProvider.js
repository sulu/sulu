// @flow
import {isArrayLike} from 'mobx';
import jsonpointer from 'json-pointer';
import type FormInspector from '../FormInspector';

export default function(data: Object, dataPath: ?string, formInspector: ?FormInspector): {[string]: any} {
    // a form can expose a parent context via its options (e.g. the block settings overlay passes the owning block)
    const parentData = formInspector && formInspector.options ? formInspector.options.__parent : undefined;

    if (!dataPath) {
        return {__parent: parentData !== undefined ? parentData : data};
    }

    let parentDataPath = dataPath;
    const conditionData = {};
    let currentConditionData = conditionData;

    do {
        parentDataPath = parentDataPath.substring(0, parentDataPath.lastIndexOf('/'));

        if (!jsonpointer.has(data, parentDataPath)) {
            currentConditionData.__parent = null;

            return conditionData;
        }

        const evaluatedData = jsonpointer.get(data, parentDataPath);

        if (isArrayLike(evaluatedData)) {
            continue;
        }

        if (parentDataPath === '' && parentData !== undefined) {
            currentConditionData.__parent = parentData;

            return conditionData;
        }

        currentConditionData.__parent = {...evaluatedData};
        currentConditionData = currentConditionData.__parent;
    } while (parentDataPath.match(/^\/.*\//));

    // the walk stopped before the form root (e.g. at an array level); graft the parent context as outermost parent
    if (parentData !== undefined) {
        currentConditionData.__parent = parentData;
    }

    return conditionData;
}
