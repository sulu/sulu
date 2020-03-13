// @flow
import updateUserStoreContentLocaleFromRouterAttributes from '../updateUserStoreContentLocaleFromRouterAttributes';
import userStore from '../../../stores/userStore/userStore';
import type {Route} from '../../Router';

const LOCALIZED_ROUTE: Route = {
    attributeDefaults: {},
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

jest.mock('../../../stores/userStore/userStore', () => {
    return {
        contentLocale: 'fr',
        updateContentLocale: jest.fn(),
    };
});

test('Should not update userStore when no locale attribute is defined', () => {
    updateUserStoreContentLocaleFromRouterAttributes(LOCALIZED_ROUTE, {});

    expect(userStore.contentLocale).toBe('fr');
});

test('Should not update userStore when route and attributes are undefined', () => {
    updateUserStoreContentLocaleFromRouterAttributes(undefined, undefined);

    expect(userStore.contentLocale).toBe('fr');
});

test('Should update userStore with attribute locale', () => {
    updateUserStoreContentLocaleFromRouterAttributes(LOCALIZED_ROUTE, {
        locale: 'de',
    });

    expect(userStore.updateContentLocale).toBeCalledWith('de');
});
