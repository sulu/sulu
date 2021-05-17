// @flow
import viewRegistry from './registries/viewRegistry';
import type {UpdateAttributesHook} from '../../services/Router/types';

const updateRouterAttributesFromView: UpdateAttributesHook = function(route, attributes: Object) {
    const parentAttributes = route.parent ? updateRouterAttributesFromView(route.parent, attributes) : {};

    const View = viewRegistry.get(route.type);

    if (typeof View.getDerivedRouteAttributes === 'function') {
        const newAttributes = View.getDerivedRouteAttributes(route, {...parentAttributes, ...attributes});

        return {...parentAttributes, ...newAttributes};
    }

    return parentAttributes;
};

export default updateRouterAttributesFromView;
