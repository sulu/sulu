// @flow
import jsonpointer from 'json-pointer';

type ResultToView = {[resultPath: string]: string};

export default function resolveItemView(
    viewName: string,
    resultToView: ResultToView,
    resultToViewName: ?ResultToView,
    item: Object
): {parameters: Object, view: string} {
    let view = viewName;

    if (resultToViewName) {
        Object.keys(resultToViewName).forEach((resultPath) => {
            view = view.replace(`{${resultToViewName[resultPath]}}`, `${jsonpointer.get(item, '/' + resultPath)}`);
        });
    }

    const parameters = Object.keys(resultToView).reduce((currentParameters, resultPath) => {
        currentParameters[resultToView[resultPath]] = jsonpointer.get(item, '/' + resultPath);
        return currentParameters;
    }, {});

    return {parameters, view};
}
