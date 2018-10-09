// @flow
import type {UpdateAttributesHook} from '../../services/Router/types';
import viewRegistry from './registries/ViewRegistry';

const updateRouterAttributesFromView: UpdateAttributesHook = function(route, attributes) {
    const View = viewRegistry.get(route.view);

    // $FlowFixMe
    if (typeof View.getDerivedRouteAttributes === 'function') {
        const newAttributes = View.getDerivedRouteAttributes(route, attributes);

        if (typeof newAttributes !== 'object') {
            throw new Error(
                'The "getDerivedRouteAttributes" function of the "' + route.view + '" view did not return an object.'
            );
        }

        return newAttributes;
    }

    return {};
};

export default updateRouterAttributesFromView;
