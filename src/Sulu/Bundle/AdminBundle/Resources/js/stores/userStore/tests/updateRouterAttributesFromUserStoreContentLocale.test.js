// @flow
import updateRouterAttributesFromUserStoreContentLocale from '../updateRouterAttributesFromUserStoreContentLocale';
import userStore from '../userStore';
import type {Route} from '../../../services/Router';

jest.mock('../../../stores/userStore/userStore', () => ({
    contentLocale: undefined,
}));

test('Should not update locale attribute when route is not localized', () => {
    userStore.contentLocale = 'fr';

    const unlocalizedRoute: Route = {
        attributeDefaults: {},
        availableAttributes: [],
        children: [],
        name: 'unlocalized_route',
        options: {},
        parent: undefined,
        path: '/example',
        rerenderAttributes: [],
        type: '',
    };

    const attributes = updateRouterAttributesFromUserStoreContentLocale(unlocalizedRoute, {});

    expect(attributes.locale).toBe(undefined);
});

test('Should not update locale attribute when locale was explicit set', () => {
    userStore.contentLocale = 'fr';

    const localizedRoute: Route = {
        attributeDefaults: {},
        availableAttributes: ['locale'],
        children: [],
        name: 'localized_route',
        options: {
            locales: ['en', 'de', 'fr'],
        },
        parent: undefined,
        path: '/:locale/example',
        rerenderAttributes: [],
        type: '',
    };

    const attributes = updateRouterAttributesFromUserStoreContentLocale(localizedRoute, {
        locale: 'de',
    });

    expect(attributes.locale).toBe('de');
});

test('Should not update locale attribute when user store locale is not available for current route', () => {
    userStore.contentLocale = 'ru';

    const localizedRoute: Route = {
        attributeDefaults: {},
        availableAttributes: ['locale'],
        children: [],
        name: 'localized_route',
        options: {
            locales: ['en', 'de', 'fr'],
        },
        parent: undefined,
        path: '/:locale/example',
        rerenderAttributes: [],
        type: '',
    };

    const attributes = updateRouterAttributesFromUserStoreContentLocale(localizedRoute, {});

    expect(attributes.locale).toBe(undefined);
});

test('Should update locale attribute from user store when not explicit set', () => {
    userStore.contentLocale = 'fr';

    const localizedRoute: Route = {
        attributeDefaults: {},
        availableAttributes: ['locale'],
        children: [],
        name: 'localized_route',
        options: {
            locales: ['en', 'de', 'fr'],
        },
        parent: undefined,
        path: '/:locale/example',
        rerenderAttributes: [],
        type: '',
    };

    const attributes = updateRouterAttributesFromUserStoreContentLocale(localizedRoute, {});

    expect(attributes.locale).toBe('fr');
});

test('Should update locale attribute from user store when view does not define specified locales', () => {
    userStore.contentLocale = 'es';

    const localizedRouteWithoutDefaultLocales: Route = {
        attributeDefaults: {},
        availableAttributes: ['locale'],
        children: [],
        name: 'localized_route_without_locales_defined',
        options: {},
        parent: undefined,
        path: '/:locale/example',
        rerenderAttributes: [],
        type: '',
    };

    const attributes = updateRouterAttributesFromUserStoreContentLocale(localizedRouteWithoutDefaultLocales, {});

    expect(attributes.locale).toBe('es');
});
