//@flow
import React from 'react';
import {render, mount} from 'enzyme';
import Router from '../../../services/Router';
import Application from '../Application';

jest.mock('../../../services/Router', () => jest.fn());

jest.mock('sulu-admin-bundle/services/ResourceRequester/ResourceRequester', () => ({
    get: jest.fn(),
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/MetadataStore', () => ({
    getSchema: jest.fn(),
    getJsonSchema: jest.fn(),
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/MemoryFormStore', () => jest.fn(
    (memoryStore) =>({memoryStore})));

const mockInitializerInitialized = jest.fn();
const mockInitializerLoading = jest.fn();
const mockInitializedTranslationsLocale = jest.fn();

jest.mock('../../../services/initializer', () => {
    return new class {
        get loading() {
            return mockInitializerLoading();
        }

        get initialized() {
            return mockInitializerInitialized();
        }

        get initializedTranslationsLocale() {
            return mockInitializedTranslationsLocale();
        }
    };
});

const mockUserStoreLoggedIn = jest.fn();
const mockUserStoreContact = jest.fn();
const mockUserStoreUser = jest.fn();
const mockUserStoreGetPersistentSetting = jest.fn().mockReturnValue(0);
const mockUserStoreSetPersistentSetting = jest.fn();

jest.mock('../../../stores/UserStore', () => {
    return new class {
        get loggedIn() {
            return mockUserStoreLoggedIn();
        }

        get user() {
            return mockUserStoreUser();
        }

        get contact() {
            return mockUserStoreContact();
        }

        getPersistentSetting(value) {
            return mockUserStoreGetPersistentSetting(value);
        }

        setPersistentSetting(name, value) {
            return mockUserStoreSetPersistentSetting(name, value);
        }
    };
});

jest.mock('../../ViewRenderer', () => function Test(props) {
    return (
        <div>
            <h1>Test</h1>
            <h2>{props.router.route.view}</h2>
        </div>
    );
});

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

beforeEach(() => {
    mockInitializerInitialized.mockReturnValue(true);
    mockInitializerLoading.mockReturnValue(false);
    mockInitializedTranslationsLocale.mockReturnValue('en');

    mockUserStoreLoggedIn.mockReturnValue(true);
    mockUserStoreContact.mockReturnValue({
        fullName: 'Hikaru Sulu',
    });
    mockUserStoreUser.mockReturnValue({
        id: 99,
        username: 'test',
    });
});

test('Render login with loader', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(true);
    mockInitializedTranslationsLocale.mockReturnValue(null);
    mockUserStoreLoggedIn.mockReturnValue(false);

    const router = new Router({});
    const application = mount(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    expect(application.render()).toMatchSnapshot();
});

test('Render login when user is not logged in', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(false);
    mockInitializedTranslationsLocale.mockReturnValue('en');
    mockUserStoreLoggedIn.mockReturnValue(false);

    const router = new Router({});
    const application = mount(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);

    expect(application.render()).toMatchSnapshot();
});

test('Should not fail if current route does not exist', () => {
    const router = new Router({});
    const view = render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);

    expect(view).toMatchSnapshot();
});

test('Render based on current route', () => {
    const router = new Router({});
    router.route = {
        name: 'test',
        view: 'test',
        attributeDefaults: {},
        rerenderAttributes: [],
        path: '/webspaces',
        children: [],
        options: {},
        parent: null,
    };

    const view = render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);

    expect(view).toMatchSnapshot();
});

test('Render based on current route with app version', () => {
    const router = new Router({});
    router.route = {
        name: 'test',
        view: 'test',
        attributeDefaults: {},
        rerenderAttributes: [],
        path: '/webspaces',
        children: [],
        options: {},
        parent: null,
    };

    const view = render(<Application appVersion="666" router={router} suluVersion="2.0.0-RC1" />);

    expect(view).toMatchSnapshot();
});

test('Render opened navigation', () => {
    const router = new Router({});
    router.route = {
        name: 'test',
        view: 'test',
        attributeDefaults: {},
        rerenderAttributes: [],
        path: '/webspaces',
        children: [],
        options: {},
        parent: null,
    };

    const view = mount(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    view.find('Button[icon="su-bars"]').simulate('click');

    expect(view.render()).toMatchSnapshot();
});

test('Pin navigation', () => {
    const router = new Router({});
    router.route = {
        name: 'test',
        view: 'test',
        attributeDefaults: {},
        rerenderAttributes: [],
        path: '/webspaces',
        children: [],
        options: {},
        parent: null,
    };

    const view = mount(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    view.find('Button[icon="su-bars"]').simulate('click');
    view.find('button.pin').simulate('click');

    expect(view.find('Navigation').at(0).prop('pinned')).toEqual(true);
    expect(mockUserStoreSetPersistentSetting).toBeCalledWith('sulu_admin.application.navigation_pinned', true);
});

test('Pin navigation from beginning', () => {
    const router = new Router({});
    router.route = {
        name: 'test',
        view: 'test',
        attributeDefaults: {},
        rerenderAttributes: [],
        path: '/webspaces',
        children: [],
        options: {},
        parent: null,
    };

    mockUserStoreGetPersistentSetting.mockReturnValueOnce(true);

    const view = mount(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    expect(view.find('Button[icon="su-bars"]')).toHaveLength(0);
    expect(view.find('Button[icon="su-sulu"]')).toHaveLength(0);
    expect(view.find('button.pin')).toHaveLength(1);

    expect(mockUserStoreGetPersistentSetting).toBeCalledWith('sulu_admin.application.navigation_pinned');

    expect(view.find('Navigation').at(0).prop('pinned')).toEqual(true);
});

test('Do not pin navigation from beginning', () => {
    const router = new Router({});
    router.route = {
        name: 'test',
        view: 'test',
        attributeDefaults: {},
        rerenderAttributes: [],
        path: '/webspaces',
        children: [],
        options: {},
        parent: null,
    };

    mockUserStoreGetPersistentSetting.mockReturnValueOnce(false);

    const view = mount(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    expect(view.find('Button[icon="su-bars"]')).toHaveLength(1);
    expect(view.find('Button[icon="su-sulu"]')).toHaveLength(0);
    expect(view.find('button.pin')).toHaveLength(1);

    expect(mockUserStoreGetPersistentSetting).toBeCalledWith('sulu_admin.application.navigation_pinned');

    expect(view.find('Navigation').at(0).prop('pinned')).toEqual(false);
});
