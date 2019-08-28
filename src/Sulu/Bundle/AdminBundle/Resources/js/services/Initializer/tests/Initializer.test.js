// @flow
import 'core-js/library/fn/promise';
import initializer from '../Initializer';
import Requester from '../../Requester';
import {setTranslations} from '../../../utils/Translator';
import resourceRouteRegistry from '../../ResourceRequester/registries/ResourceRouteRegistry';

jest.mock('../../ResourceRequester/registries/ResourceRouteRegistry', () => ({
    setRoutingData: jest.fn(),
}));

jest.mock('../../Requester', () => ({
    get: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    setTranslations: jest.fn(),
}));

beforeEach(() => {
    initializer.clear();
});

test('Should initialize when everything works', () => {
    const configData = {
        sulu_admin: {
            fieldTypeOptions: {
                selection: {
                    contact_selection: {
                        resourceKey: 'contacts',
                    },
                },
                single_selection: {
                    single_account_selection: {
                        resourceKey: 'accounts',
                    },
                },
            },
            routes: 'crazy_routes',
            navigation: 'nice_navigation',
            resourceMetadataEndpoints: 'top_endpoints',
            user: 'the_logged_in_user',
            contact: 'contact_of_the_user',
            smartContent: {
                content: {
                    datasourceResourceKey: 'pages',
                },
            },
        },
    };

    const translationData = {
        'sulu_admin.test1': 'Test1',
    };

    const routeData = {};

    const translationPromise = Promise.resolve(translationData);
    const configPromise = Promise.resolve(configData);
    const routePromise = Promise.resolve(routeData);

    Requester.get.mockImplementation((key) => {
        switch (key) {
            case 'translations_url?locale=en':
                return translationPromise;
            case 'config_url':
                return configPromise;
            case 'routing':
                return routePromise;
        }
    });

    const hook = jest.fn();
    initializer.addUpdateConfigHook('sulu_admin', hook);

    const initPromise = initializer.initialize(true);
    expect(initializer.loading).toBe(true);

    return initPromise
        .then(() => {
            expect(resourceRouteRegistry.setRoutingData).toBeCalledWith(routeData);
            expect(setTranslations).toBeCalledWith(translationData, 'en');
            expect(initializer.initializedTranslationsLocale).toBe('en');

            expect(initializer.initialized).toBe(true);
            expect(initializer.loading).toBe(false);

            expect(hook).toBeCalledWith(configData['sulu_admin'], false);
        });
});

test('Should only initialize translations if no user is logged in', () => {
    const translationData = {
        'sulu_admin.test1': 'Test1',
    };
    const translationPromise = Promise.resolve(translationData);

    Requester.get.mockImplementation((key) => {
        switch (key) {
            case 'translations_url?locale=en':
                return translationPromise;
        }
    });

    const hook = jest.fn();
    initializer.addUpdateConfigHook('sulu_admin', hook);

    const initPromise = initializer.initialize(false);
    expect(initializer.loading).toBe(true);

    return initPromise
        .then(() => {
            expect(resourceRouteRegistry.setRoutingData).not.toBeCalled();
            expect(setTranslations).toBeCalledWith(translationData, 'en');
            expect(initializer.initializedTranslationsLocale).toBe('en');

            expect(initializer.initialized).toBe(false);
            expect(initializer.loading).toBe(false);

            expect(hook).not.toBeCalled();
        });
});

test('Should not reinitialize everything when it was already initialized', () => {
    const configData = {
        'sulu_admin': {
            routes: 'crazy_routes',
            navigation: 'nice_navigation',
            resourceMetadataEndpoints: 'top_endpoints',
            user: 'the_logged_in_user',
            contact: 'contact_of_the_user',
        },
    };

    const translationData = {
        'sulu_admin.test1': 'Test1',
    };

    const routeData = {};

    const translationPromise = Promise.resolve(translationData);
    const configPromise = Promise.resolve(configData);
    const routePromise = Promise.resolve(routeData);

    Requester.get.mockImplementation((key) => {
        switch (key) {
            case 'translations_url?locale=en':
                return translationPromise;
            case 'config_url':
                return configPromise;
            case 'routing':
                return routePromise;
        }
    });

    initializer.setInitialized();
    initializer.setInitializedTranslationsLocale('en');

    const initPromise = initializer.initialize(true);
    expect(initializer.loading).toBe(true);

    return initPromise
        .then(() => {
            expect(resourceRouteRegistry.setRoutingData).toBeCalledWith(routeData);
            expect(setTranslations).not.toBeCalled();

            expect(initializer.initialized).toBe(true);
            expect(initializer.loading).toBe(false);
        });
});

test('Should not crash when the config request throws an 401 error', () => {
    const translationData = {
        'sulu_admin.test1': 'Test1',
    };

    const translationPromise = Promise.resolve(translationData);
    const configPromise = Promise.reject({status: 401});
    const routePromise = Promise.resolve();

    Requester.get.mockImplementation((key) => {
        switch (key) {
            case 'translations_url?locale=en':
                return translationPromise;
            case 'config_url':
                return configPromise;
            case 'routing':
                return routePromise;
        }
    });

    const initPromise = initializer.initialize(true);
    expect(initializer.loading).toBe(true);

    return initPromise
        .catch(() => {
            expect(setTranslations).toBeCalledWith(translationData);
            expect(initializer.initializedTranslationsLocale).toBe('en');
            expect(initializer.initialized).toBe(false);
            expect(initializer.loading).toBe(false);
        });
});

test('Should clear the initializer', () => {
    initializer.setLoading(true);
    initializer.setInitializedTranslationsLocale('en');
    initializer.setInitialized();

    expect(initializer.loading).toBe(true);
    expect(initializer.initializedTranslationsLocale).toBe('en');
    expect(initializer.initialized).toBe(true);

    initializer.clear();

    expect(initializer.loading).toBe(false);
    expect(initializer.initializedTranslationsLocale).toBeUndefined();
    expect(initializer.initialized).toBe(false);
});
