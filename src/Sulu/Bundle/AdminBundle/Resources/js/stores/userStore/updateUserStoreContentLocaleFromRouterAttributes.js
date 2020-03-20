// @flow
import pathToRegexp from 'path-to-regexp';
import type {UpdateRouteHook} from '../../services/Router/types';
import userStore from './userStore';

const updateUserStoreContentLocaleFromRouterAttributes: UpdateRouteHook = function(newRoute, newAttributes) {
    if (!newRoute || !newAttributes) {
        return true;
    }

    // do nothing when the route does not require a locale
    if (!newRoute.availableAttributes.includes('locale')) {
        return true;
    }

    if (newAttributes.locale) {
        userStore.updateContentLocale(newAttributes.locale);
    }

    return true;
};

export default updateUserStoreContentLocaleFromRouterAttributes;
