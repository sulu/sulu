// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Router, {Route} from '../../../services/Router';
import Application from '../Application';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';
import loaderStyles from '../../../components/Loader/loader.scss';

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../../../services/Router/Router', () => jest.fn(function() {
    this.attributes = {};
    this.reload = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/MemoryFormStore', () => jest.fn((memoryStore) => ({memoryStore})));

const mockInitializerInitialized = jest.fn();
const mockInitializerLoading = jest.fn();
const mockInitializedTranslationsLocale = jest.fn();

jest.mock('../../../services/initializer', () => new class {
    get loading() {
        return mockInitializerLoading();
    }

    get initialized() {
        return mockInitializerInitialized();
    }

    get initializedTranslationsLocale() {
        return mockInitializedTranslationsLocale();
    }
});

const mockUserStoreLoggedIn = jest.fn();
const mockUserStoreContact = jest.fn();
const mockUserStoreUser = jest.fn();
const mockUserStoreGetPersistentSetting = jest.fn().mockReturnValue(false);
const mockUserStoreSetPersistentSetting = jest.fn();
const mockUserStoreHasSingleSignOn = jest.fn().mockReturnValue(false);

jest.mock('../../../stores/userStore', () => new class {
    get loggedIn() {
        return mockUserStoreLoggedIn();
    }

    get user() {
        return mockUserStoreUser();
    }

    get contact() {
        return mockUserStoreContact();
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

    logout() {
        return Promise.resolve();
    }
});

jest.mock('../../Navigation', () => {
    const React = require('react');

    return jest.fn(function NavigationMock(props) {
        const handlePinToggle = () => props.onPinToggle();
        const handleNavigate = () => props.onNavigate();
        const handleLogout = () => props.onLogout();
        const handleProfileClick = () => props.onProfileClick();

        return React.createElement(
            'div',
            {'data-pinned': props.pinned ? 'true' : 'false', 'data-testid': 'navigation'},
            React.createElement('button', {onClick: handlePinToggle, type: 'button'}, 'pin-toggle'),
            React.createElement('button', {onClick: handleNavigate, type: 'button'}, 'navigate'),
            React.createElement('button', {onClick: handleLogout, type: 'button'}, 'logout'),
            React.createElement('button', {onClick: handleProfileClick, type: 'button'}, 'profile')
        );
    });
});

jest.mock('../../Toolbar', () => {
    const React = require('react');

    return jest.fn(function ToolbarMock({navigationOpen, onNavigationButtonClick}) {
        return React.createElement(
            'div',
            {'data-open': navigationOpen ? 'true' : 'false', 'data-testid': 'toolbar'},
            onNavigationButtonClick
                ? React.createElement('button', {onClick: onNavigationButtonClick, type: 'button'}, 'toggle-nav')
                : null
        );
    });
});

jest.mock('../../ViewRenderer', () => {
    const React = require('react');

    return jest.fn(function ViewRendererMock({router}) {
        return React.createElement('div', {'data-testid': 'view-renderer'}, router.route.type);
    });
});

jest.mock('../../Login', () => {
    const React = require('react');

    return jest.fn(function LoginMock({initialized, router}) {
        return React.createElement(
            'div',
            {'data-testid': 'login'},
            initialized ? 'initialized' : 'not-initialized',
            router.attributes.forgotPasswordToken ? '-reset' : ''
        );
    });
});

jest.mock('../../Sidebar', () => {
    const React = require('react');
    const sidebarStore = {size: undefined, view: undefined};

    return {
        __esModule: true,
        default: jest.fn(function SidebarMock() {
            return React.createElement('div', {'data-testid': 'sidebar'});
        }),
        sidebarStore,
    };
});

jest.mock('../../ProfileFormOverlay', () => {
    const React = require('react');

    return jest.fn(function ProfileFormOverlayMock({open}) {
        return React.createElement('div', {'data-open': open ? 'true' : 'false', 'data-testid': 'profile-overlay'});
    });
});

const navigationMock = (jest.requireMock('../../Navigation'): any);
const toolbarMock = (jest.requireMock('../../Toolbar'): any);

function getLatestNavigationProps(): any {
    return getLatestMockProps(navigationMock);
}

function getLatestToolbarProps(): any {
    return getLatestMockProps(toolbarMock);
}

function createRouter(routeType?: string): Router {
    const router = new Router({});

    if (routeType) {
        router.route = new Route({
            name: 'test',
            path: '/webspaces',
            type: routeType,
        });
    }

    return router;
}

beforeEach(() => {
    jest.clearAllMocks();

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
    mockUserStoreGetPersistentSetting.mockReturnValue(false);
});

test('Render login with loader', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(true);
    mockInitializedTranslationsLocale.mockReturnValue(null);
    mockUserStoreLoggedIn.mockReturnValue(false);

    const {asFragment} = render(<Application appVersion={null} router={createRouter()} suluVersion="2.0.0-RC1" />);
    expect(document.querySelector(`.${loaderStyles.spinner}`)).not.toBeNull();
    expect(screen.getByTestId('login')).toHaveTextContent('not-initialized');
    expect(asFragment()).toMatchSnapshot();
});

test('Render login screen to reset password', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(false);
    mockInitializedTranslationsLocale.mockReturnValue('en');
    mockUserStoreLoggedIn.mockReturnValue(false);

    const router = createRouter();
    router.attributes.forgotPasswordToken = 'some-uuid';
    const {asFragment} = render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);

    expect(screen.getByTestId('login')).toHaveTextContent('initialized-reset');
    expect(asFragment()).toMatchSnapshot();
});

test('Render login when user is not logged in', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(false);
    mockInitializedTranslationsLocale.mockReturnValue('en');
    mockUserStoreLoggedIn.mockReturnValue(false);

    const {asFragment} = render(<Application appVersion={null} router={createRouter()} suluVersion="2.0.0-RC1" />);
    expect(screen.getByTestId('login')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Should not fail if current route does not exist', () => {
    const {asFragment} = render(<Application appVersion={null} router={createRouter()} suluVersion="2.0.0-RC1" />);
    expect(screen.queryByTestId('view-renderer')).not.toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Render based on current route', () => {
    const {asFragment} = render(
        <Application appVersion={null} router={createRouter('test')} suluVersion="2.0.0-RC1" />
    );
    expect(screen.getByTestId('view-renderer')).toHaveTextContent('test');
    expect(asFragment()).toMatchSnapshot();
});

test('Render based on current route with app version', () => {
    const {asFragment} = render(<Application appVersion="666" router={createRouter('test')} suluVersion="2.0.0-RC1" />);

    expect(getLatestNavigationProps().appVersion).toEqual('666');
    expect(asFragment()).toMatchSnapshot();
});

test('Render opened navigation', async() => {
    const user = userEvent.setup();
    const {asFragment} = render(
        <Application appVersion={null} router={createRouter('test')} suluVersion="2.0.0-RC1" />
    );

    await user.click(screen.getByRole('button', {name: 'toggle-nav'}));

    expect(screen.getByTestId('backdrop')).toBeInTheDocument();
    expect(getLatestToolbarProps().navigationOpen).toEqual(true);
    expect(asFragment()).toMatchSnapshot();
});

test('Pin navigation', async() => {
    const user = userEvent.setup();
    render(<Application appVersion={null} router={createRouter('test')} suluVersion="2.0.0-RC1" />);

    await user.click(screen.getByRole('button', {name: 'toggle-nav'}));
    await user.click(screen.getByRole('button', {name: 'pin-toggle'}));

    expect(getLatestNavigationProps().pinned).toEqual(true);
    expect(mockUserStoreSetPersistentSetting).toHaveBeenCalledWith('sulu_admin.application.navigation_pinned', true);
});

test('Pin navigation from beginning', () => {
    mockUserStoreGetPersistentSetting.mockReturnValueOnce(true);
    render(<Application appVersion={null} router={createRouter('test')} suluVersion="2.0.0-RC1" />);

    expect(getLatestToolbarProps().onNavigationButtonClick).toBeUndefined();
    expect(getLatestNavigationProps().pinned).toEqual(true);
    expect(mockUserStoreGetPersistentSetting).toHaveBeenCalledWith('sulu_admin.application.navigation_pinned');
});

test('Do not pin navigation from beginning', () => {
    mockUserStoreGetPersistentSetting.mockReturnValueOnce(false);
    render(<Application appVersion={null} router={createRouter('test')} suluVersion="2.0.0-RC1" />);

    expect(typeof getLatestToolbarProps().onNavigationButtonClick).toEqual('function');
    expect(getLatestNavigationProps().pinned).toEqual(false);
    expect(mockUserStoreGetPersistentSetting).toHaveBeenCalledWith('sulu_admin.application.navigation_pinned');
});
