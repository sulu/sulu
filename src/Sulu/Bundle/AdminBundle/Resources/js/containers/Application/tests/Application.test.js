//@flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Router, {Route} from '../../../services/Router';
import Application from '../Application';

jest.mock('../../../services/Router/Router', () => jest.fn(function() {
    this.attributes = {};
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/MemoryFormStore', () => jest.fn((memoryStore) => ({memoryStore})));

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

        get redirectUrl() {
            return '';
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

jest.mock('../../ViewRenderer', () => function Test(props) {
    return (
        <div>
            <h1>Test</h1>
            <h2>{props.router.route.type}</h2>
        </div>
    );
});

jest.mock('../../ProfileFormOverlay', () => function Test() {
    return (
        <div>ProfileFormOverlay Mock</div>
    );
});

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
    const {asFragment} = render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render login screen to reset password', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(false);
    mockInitializedTranslationsLocale.mockReturnValue('en');
    mockUserStoreLoggedIn.mockReturnValue(false);

    const router = new Router({});
    router.attributes.forgotPasswordToken = 'some-uuid';
    const {asFragment} = render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render login when user is not logged in', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(false);
    mockInitializedTranslationsLocale.mockReturnValue('en');
    mockUserStoreLoggedIn.mockReturnValue(false);

    const router = new Router({});
    const {asFragment} = render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Should not fail if current route does not exist', () => {
    const router = new Router({});
    const {asFragment} = render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render based on current route', () => {
    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    const {asFragment} = render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render based on current route with app version', () => {
    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    const {asFragment} = render(<Application appVersion="666" router={router} suluVersion="2.0.0-RC1" />);

    expect(asFragment()).toMatchSnapshot();
});

test('Render opened navigation', async() => {
    const user = userEvent.setup();
    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    const {asFragment} = render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    await user.click(screen.getByRole('button', {name: 'su-bars'}));

    expect(asFragment()).toMatchSnapshot();
});

test('Pin navigation', async() => {
    const user = userEvent.setup();
    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    await user.click(screen.getByRole('button', {name: 'su-bars'}));
    await user.click(screen.getByRole('button', {name: 'su-stick-right'}));

    expect(screen.queryByRole('button', {name: 'su-bars'})).not.toBeInTheDocument();
    expect(mockUserStoreSetPersistentSetting).toBeCalledWith('sulu_admin.application.navigation_pinned', true);
});

test('Pin navigation from beginning', () => {
    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    mockUserStoreGetPersistentSetting.mockReturnValueOnce(true);

    render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    expect(screen.queryByRole('button', {name: 'su-bars'})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'su-sulu-logo'})).not.toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'su-stick-right'})).toBeInTheDocument();

    expect(mockUserStoreGetPersistentSetting).toBeCalledWith('sulu_admin.application.navigation_pinned');
});

test('Do not pin navigation from beginning', () => {
    const router = new Router({});
    router.route = new Route({
        name: 'test',
        path: '/webspaces',
        type: 'test',
    });

    mockUserStoreGetPersistentSetting.mockReturnValueOnce(false);

    render(<Application appVersion={null} router={router} suluVersion="2.0.0-RC1" />);
    expect(screen.getByRole('button', {name: 'su-bars'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'su-sulu-logo'})).not.toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'su-stick-right'})).toBeInTheDocument();

    expect(mockUserStoreGetPersistentSetting).toBeCalledWith('sulu_admin.application.navigation_pinned');
});
