// @flow
import pathToRegexp from 'path-to-regexp';
import {toJS} from 'mobx';
import type {AttributeMap, Route, UpdateAttributesHook} from '../Router/types';
import userStore from '../../stores/userStore/userStore';

const updateRouterAttributesFromUserStoreContentLocale: UpdateAttributesHook = function(
    route: Route,
    attributes: AttributeMap
) {
    // do nothing when locale is explicit set
    if (attributes.locale) {
        return attributes;
    }

    const keys = [];
    pathToRegexp(route.path, keys);
    const keyNames = keys.map((key) => key.name);

    // do nothing when the route does not require a locale
    if (!keyNames.includes('locale')) {
        return attributes;
    }

    const locales = toJS(route.options.locales);

    // set content locale if route accept the current content locale
    if (locales && locales.includes(userStore.contentLocale)) {
        attributes.locale = userStore.contentLocale;
    }

    return attributes;
};

export default updateRouterAttributesFromUserStoreContentLocale;
