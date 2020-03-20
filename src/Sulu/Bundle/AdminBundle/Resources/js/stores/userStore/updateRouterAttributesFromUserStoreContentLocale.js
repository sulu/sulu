// @flow
import {toJS} from 'mobx';
import type {AttributeMap, Route, UpdateAttributesHook} from '../../services/Router/types';
import userStore from './userStore';

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
    // or does not specify which locales it accepts e.g.: PageList
    if (!locales || locales.includes(userStore.contentLocale)) {
        attributes.locale = userStore.contentLocale;
    }

    return attributes;
};

export default updateRouterAttributesFromUserStoreContentLocale;
