// @flow
import userStore from './userStore';
import type {UpdateRouteHook} from '../../services/Router/types';

const updateUserStoreContentLocaleFromRouterAttributes: UpdateRouteHook = function(newRoute, newAttributes) {
    if (!newRoute || !newAttributes) {
        return true;
    }

    // do nothing when the route does not require a locale
    if (!newRoute.availableAttributes.includes('locale')) {
        return true;
    }

    if (newAttributes.locale) {
        const locale = typeof newAttributes.locale.get === 'function'
            // $FlowFixMe
            ? newAttributes.locale.get()
            : newAttributes.locale;

        if (typeof locale !== 'string') {
            throw new Error('The "locale" router attribute must be a string if given!');
        }

        userStore.updateContentLocale(locale);
    }

    return true;
};

export default updateUserStoreContentLocaleFromRouterAttributes;
