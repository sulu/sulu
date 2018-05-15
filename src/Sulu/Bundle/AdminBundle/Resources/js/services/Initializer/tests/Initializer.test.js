// @flow
import 'core-js/library/fn/promise';
import initializer from '../Initializer';
import Requester from '../../Requester';
import {setTranslations} from '../../../utils/Translator';
import fieldRegistry from '../../../containers/Form/registries/FieldRegistry';
import routeRegistry from '../../Router/registries/RouteRegistry';
import navigationRegistry from '../../../containers/Navigation/registries/NavigationRegistry';
import resourceMetadataStore from '../../../stores/ResourceMetadataStore';
import userStore from '../../../stores/UserStore';
import datagridAdapterRegistry from '../../../containers/Datagrid/registries/DatagridAdapterRegistry';
import datagridFieldTransformerRegistry from '../../../containers/Datagrid/registries/DatagridFieldTransformerRegistry';
import {bundlesReadyPromise} from '../../Bundles';

jest.mock('../../Requester', () => ({
    get: jest.fn(),
}));

jest.mock('../../Bundles', () => ({
    bundlesReadyPromise: Promise.resolve(),
}));

jest.mock('../../../utils/Translator', () => ({
    setTranslations: jest.fn(),
}));

jest.mock('../../../containers/Form/registries/FieldRegistry', () => ({
    add: jest.fn(),
}));

jest.mock('../../Router/registries/RouteRegistry', () => ({
    addCollection: jest.fn(),
}));

jest.mock('../../../containers/Navigation/registries/NavigationRegistry', () => ({
    set: jest.fn(),
}));

jest.mock('../../../stores/ResourceMetadataStore', () => ({
    setEndpoints: jest.fn(),
}));

jest.mock('../../../stores/UserStore', () => ({
    setUser: jest.fn(),
    setContact: jest.fn(),
    setLoggedIn: jest.fn(),
}));

jest.mock('../../../containers/Datagrid/registries/DatagridAdapterRegistry', () => ({
    add: jest.fn(),
}));

jest.mock('../../../containers/Datagrid/registries/DatagridFieldTransformerRegistry', () => ({
    add: jest.fn(),
}));

beforeEach(() => {
    initializer.clear();
});

test('Should initialize when everything works', () => {
    const configData = {
        'sulu_admin': {
            field_type_options: '123',
            routes: 'crazy_routes',
            navigation: 'nice_navigation',
            endpoints: 'top_endpoints',
            user: 'the_logged_in_user',
            contact: 'contact_of_the_user',
        },
    };

    const translationData = {
        'sulu_admin.test1': 'Test1',
    };

    const translationPromise = Promise.resolve(translationData);
    const configPromise = Promise.resolve(configData);

    Requester.get.mockImplementation((key) => {
        switch (key) {
            case '/admin/v2/translations?locale=en':
                return translationPromise;
            case '/admin/v2/config':
                return configPromise;
        }
    });

    const initPromise = initializer.initialize();

    bundlesReadyPromise.then(() => {
        expect(initializer.loading).toBe(true);
    });

    return initPromise
        // Bug in flow: https://github.com/facebook/flow/issues/5810
        // $FlowFixMe:
        .finally(() => {
            expect(setTranslations).toBeCalledWith(translationData);
            expect(initializer.translationInitialized).toBe(true);

            expect(fieldRegistry.add).toBeCalled();
            expect(routeRegistry.addCollection).toBeCalledWith('crazy_routes');
            expect(navigationRegistry.set).toBeCalledWith('nice_navigation');
            expect(resourceMetadataStore.setEndpoints).toBeCalledWith('top_endpoints');
            expect(userStore.setContact).toBeCalledWith('contact_of_the_user');
            expect(userStore.setUser).toBeCalledWith('the_logged_in_user');

            expect(initializer.initialized).toBe(true);
            expect(initializer.loading).toBe(false);
        });
});

test('Should not crash when the config request throws error', () => {
    const translationData = {
        'sulu_admin.test1': 'Test1',
    };

    const translationPromise = Promise.resolve(translationData);
    const configPromise = Promise.reject('Heavy Error! For example 401');

    Requester.get.mockImplementation((key) => {
        switch (key) {
            case '/admin/v2/translations?locale=en':
                return translationPromise;
            case '/admin/v2/config':
                return configPromise;
        }
    });

    const initPromise = initializer.initialize();

    bundlesReadyPromise.then(() => {
        expect(initializer.loading).toBe(true);
    });

    return initPromise
        // Bug in flow: https://github.com/facebook/flow/issues/5810
        // $FlowFixMe:
        .finally(() => {
            expect(setTranslations).toBeCalledWith(translationData);
            expect(initializer.translationInitialized).toBe(true);
            expect(initializer.initialized).toBe(false);
            expect(initializer.loading).toBe(false);
        });
});

test('Should register datagrid correctly', () => {
    initializer.registerDatagrid();
    expect(datagridAdapterRegistry.add).toBeCalled();
    expect(datagridFieldTransformerRegistry.add).toBeCalled();
});

test('Should clear the initializer', () => {
    initializer.setLoading(true);
    initializer.setTranslationInitialized(true);
    initializer.setInitialized(true);

    expect(initializer.loading).toBe(true);
    expect(initializer.translationInitialized).toBe(true);
    expect(initializer.initialized).toBe(true);

    initializer.clear();

    expect(initializer.loading).toBe(false);
    expect(initializer.translationInitialized).toBe(false);
    expect(initializer.initialized).toBe(false);
});
