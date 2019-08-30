// @flow
import type {UpdateAttributesHook} from '../../services/Router/types';
import viewRegistry from './registries/viewRegistry';

const updateRouterAttributesFromView: UpdateAttributesHook = function(route, attributes) {
    const parentAttributes = route.parent ? updateRouterAttributesFromView(route.parent, attributes) : {};

    const View = viewRegistry.get(route.view);

    if (typeof View.getDerivedRouteAttributes === 'function') {
        const newAttributes = View.getDerivedRouteAttributes(route, {...parentAttributes, ...attributes});

        return {...parentAttributes, ...newAttributes};
    }

    return parentAttributes;
};

export default updateRouterAttributesFromView;
