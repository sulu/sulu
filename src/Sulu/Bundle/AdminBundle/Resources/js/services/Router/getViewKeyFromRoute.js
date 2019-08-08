// @flow
import type {Route, AttributeMap} from './types';

export default function getViewKeyFromRoute(route: ?Route, attributes: ?AttributeMap) {
    if (!route) {
        return null;
    }

    const rerenderAttributeValues = [];

    if (route.rerenderAttributes) {
        route.rerenderAttributes.forEach((rerenderAttribute) => {
            if (attributes && attributes.hasOwnProperty(rerenderAttribute)) {
                rerenderAttributeValues.push(attributes[rerenderAttribute]);
            }
        });
    }

    return route.name + (rerenderAttributeValues.length > 0 ? '-' + rerenderAttributeValues.join('__') : '');
}
