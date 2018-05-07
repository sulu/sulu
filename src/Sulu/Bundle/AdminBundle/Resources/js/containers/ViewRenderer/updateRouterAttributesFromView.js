// @flow
import type {UpdateAttributesHook} from '../../services/Router/types';
import viewRegistry from './registries/ViewRegistry';

const updateRouterAttributesFromView: UpdateAttributesHook = function (route) {
    const View = viewRegistry.get(route.view);

    if ('function' === typeof View.getDerivedRouteAttributes) {
        const attributes = View.getDerivedRouteAttributes(route);

        if ('object' !== typeof attributes) {
            throw new Error(
                'The "getDerivedRouteAttributes" function of the "' + route.view + '" view did not return an object.'
            );
        }

        return attributes;
    }

    return {};
};

export default updateRouterAttributesFromView;
