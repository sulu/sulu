//@flow
import React from 'react';
import {render, mount} from 'enzyme';
import Router, {Route} from '../../../services/Router';
import Application from '../Application';
import blockingOverlayRegistry from '../registries/blockingOverlayRegistry';

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../../../services/Router/Router', () => jest.fn(function() {
    this.attributes = {};
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/MemoryFormStore', () => jest.fn((memoryStore) =>({memoryStore})));

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
const mockUserStoreHasSingleSignOn = jest.fn().mockReturnValue(false);
const mockUserStoreTwoFactorSetupRequired = jest.fn().mockReturnValue(false);

jest.mock('../../../stores/userStore', () => {
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

        get twoFactorSetupRequired() {
            return mockUserStoreTwoFactorSetupRequired();
        }

        hasSingleSignOn() {
            return mockUserStoreHasSingleSignOn();
        }

        getPersistentSetting(value) {
            return mockUserStoreGetPersistentSetting(value);
        }

        setPersistentSetting(name, value) {
            return mockUserStoreSetPersistentSetting(name, value);
        }
    };
});

jest.mock('../../ViewRenderer', () => function ViewRenderer(props) {
    return (
        <div>
            <h1>Test</h1>
            <h2>{props.router.route.type}</h2>
        </div>
    );
});

jest.mock('../../ProfileFormOverlay', () => function ProfileFormOverlay() {
    return (
        <div>ProfileFormOverlay Mock</div>
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
    mockUserStoreTwoFactorSetupRequired.mockReturnValue(false);
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

test('Render login screen to reset password', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(false);
    mockInitializedTranslationsLocale.mockReturnValue('en');
    mockUserStoreLoggedIn.mockReturnValue(false);

    const router = new Router({});
    router.attributes.forgotPasswordToken = 'some-uuid';
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
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    const view = render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);

    expect(view).toMatchSnapshot();
});

test('Do not render the view while a forced two factor setup is pending', () => {
    mockUserStoreTwoFactorSetupRequired.mockReturnValue(true);

    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    const view = mount(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);

    expect(view.find('ViewRenderer')).toHaveLength(0);
});

test('Render the registered blocking overlays', () => {
    blockingOverlayRegistry.clear();
    blockingOverlayRegistry.add('test_overlay', function TestBlockingOverlay() {
        return <div>blocking overlay mock</div>;
    });

    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    const view = mount(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);

    expect(view.find('TestBlockingOverlay')).toHaveLength(1);

    blockingOverlayRegistry.clear();
});

test('Render based on current route with app version', () => {
    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    const view = render(<Application appVersion="666" router={router} suluVersion="2.0.0-RC1" />);

    expect(view).toMatchSnapshot();
});

test('Render opened navigation', () => {
    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    const view = mount(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    view.find('Button[icon="su-bars"]').simulate('click');

    expect(view.render()).toMatchSnapshot();
});

test('Pin navigation', () => {
    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    const view = mount(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    view.find('Button[icon="su-bars"]').simulate('click');
    view.find('.pin').simulate('click');

    expect(view.find('Navigation').at(0).prop('pinned')).toEqual(true);
    expect(mockUserStoreSetPersistentSetting).toHaveBeenCalledWith('sulu_admin.application.navigation_pinned', true);
});

test('Pin navigation from beginning', () => {
    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    mockUserStoreGetPersistentSetting.mockReturnValueOnce(true);

    const view = mount(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    expect(view.find('Button[icon="su-bars"]')).toHaveLength(0);
    expect(view.find('Button[icon="su-sulu-logo"]')).toHaveLength(0);
    expect(view.find('.pin')).toHaveLength(1);

    expect(mockUserStoreGetPersistentSetting).toHaveBeenCalledWith('sulu_admin.application.navigation_pinned');

    expect(view.find('Navigation').at(0).prop('pinned')).toEqual(true);
});

test('Do not pin navigation from beginning', () => {
    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    mockUserStoreGetPersistentSetting.mockReturnValueOnce(false);

    const view = mount(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    expect(view.find('Button[icon="su-bars"]')).toHaveLength(1);
    expect(view.find('Button[icon="su-sulu-logo"]')).toHaveLength(0);
    expect(view.find('.pin')).toHaveLength(1);

    expect(mockUserStoreGetPersistentSetting).toHaveBeenCalledWith('sulu_admin.application.navigation_pinned');

    expect(view.find('Navigation').at(0).prop('pinned')).toEqual(false);
});
