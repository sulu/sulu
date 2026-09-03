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
        const value = jsonpointer.get(item, '/' + resultPath);

        // omit a nullish value instead of passing it on, so the router's own attribute
        // defaults (e.g. the current content locale) can still apply for it
        if (value !== null && value !== undefined) {
            currentParameters[resultToView[resultPath]] = value;
        }

        return currentParameters;
    }, {});

    return {parameters, view};
}
