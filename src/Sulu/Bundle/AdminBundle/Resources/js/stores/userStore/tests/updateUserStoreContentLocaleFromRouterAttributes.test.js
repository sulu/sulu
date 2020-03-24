// @flow
import updateUserStoreContentLocaleFromRouterAttributes from '../updateUserStoreContentLocaleFromRouterAttributes';
import userStore from '../userStore';
import {Route} from '../../../services/Router';

jest.mock('../../../stores/userStore/userStore', () => ({
    contentLocale: 'fr',
    updateContentLocale: jest.fn(),
}));

test('Should not update userStore when no locale attribute is defined', () => {
    const localizedRoute = new Route({
        name: 'localized_route',
        options: {
            locales: ['en', 'de', 'fr'],
        },
        path: '/:locale/example',
        type: 'example',
    });

    updateUserStoreContentLocaleFromRouterAttributes(localizedRoute, {});

    expect(userStore.contentLocale).toBe('fr');
});

test('Should not update userStore when route and attributes are undefined', () => {
    updateUserStoreContentLocaleFromRouterAttributes(undefined, undefined);

    expect(userStore.contentLocale).toBe('fr');
});

test('Should update userStore with attribute locale', () => {
    const localizedRoute = new Route({
        name: 'localized_route',
        options: {
            locales: ['en', 'de', 'fr'],
        },
        path: '/:locale/example',
        type: 'example',
    });

    updateUserStoreContentLocaleFromRouterAttributes(localizedRoute, {
        locale: 'de',
    });

    expect(userStore.updateContentLocale).toBeCalledWith('de');
});
