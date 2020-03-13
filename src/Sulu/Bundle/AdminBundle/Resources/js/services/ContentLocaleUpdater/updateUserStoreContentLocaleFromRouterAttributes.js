// @flow
import type {UpdateRouteHook} from '../Router/types';
import userStore from '../../stores/userStore/userStore';

const updateUserStoreContentLocaleFromRouterAttributes: UpdateRouteHook = function(newRoute, newAttributes) {
    if (!newAttributes) {
        return true;
    }

    if (newAttributes.locale) {
        userStore.updateContentLocale(newAttributes.locale);
    }

    return true;
};

export default updateUserStoreContentLocaleFromRouterAttributes;
