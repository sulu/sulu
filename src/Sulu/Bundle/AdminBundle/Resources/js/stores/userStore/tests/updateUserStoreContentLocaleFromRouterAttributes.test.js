// @flow
import updateUserStoreContentLocaleFromRouterAttributes from '../updateUserStoreContentLocaleFromRouterAttributes';
import userStore from '../userStore';
import type {Route} from '../../../services/Router';

jest.mock('../../../stores/userStore/userStore', () => ({
    contentLocale: 'fr',
    updateContentLocale: jest.fn(),
}));

test('Should not update userStore when no locale attribute is defined', () => {
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

    updateUserStoreContentLocaleFromRouterAttributes(localizedRoute, {});

    expect(userStore.contentLocale).toBe('fr');
});

test('Should not update userStore when route and attributes are undefined', () => {
    updateUserStoreContentLocaleFromRouterAttributes(undefined, undefined);

    expect(userStore.contentLocale).toBe('fr');
});

test('Should update userStore with attribute locale', () => {
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

    updateUserStoreContentLocaleFromRouterAttributes(localizedRoute, {
        locale: 'de',
    });

    expect(userStore.updateContentLocale).toBeCalledWith('de');
});
