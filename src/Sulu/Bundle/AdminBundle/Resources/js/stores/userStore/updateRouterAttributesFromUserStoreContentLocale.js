// @flow
import {toJS} from 'mobx';
import {Route} from '../../services/Router';
import userStore from './userStore';
import type {AttributeMap, UpdateAttributesHook} from '../../services/Router/types';

const updateRouterAttributesFromUserStoreContentLocale: UpdateAttributesHook = function(
    route: Route,
    attributes: AttributeMap
) {
    // do nothing when locale is explicit set
    if (attributes.locale) {
        return attributes;
    }

    // do nothing when the route does not require a locale
    if (!route.availableAttributes.includes('locale')) {
        return attributes;
    }

    const locales = toJS(route.options.locales);

    // set content locale if route accept the current content locale
    if (!locales || locales.includes(userStore.contentLocale)) {
        attributes.locale = userStore.contentLocale;
    }

    return attributes;
};

export default updateRouterAttributesFromUserStoreContentLocale;
