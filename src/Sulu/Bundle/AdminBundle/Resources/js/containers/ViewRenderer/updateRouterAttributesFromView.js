// @flow
import type {UpdateAttributesHook} from '../../services/Router/types';
import viewRegistry from './registries/ViewRegistry';

const updateRouterAttributesFromView: UpdateAttributesHook = function (route) {
    const View = viewRegistry.get(route.view);

    if (typeof(View.getDerivedRouteAttributes) === 'function') {
        return View.getDerivedRouteAttributes(route);
    }

    return {};
};

export default updateRouterAttributesFromView;
