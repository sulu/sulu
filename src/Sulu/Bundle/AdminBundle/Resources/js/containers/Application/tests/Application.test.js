// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Router, {Route} from '../../../services/Router';
import Application from '../Application';

jest.mock('../../../utils/Translator');

jest.mock('../../../services/Router/Router', () => jest.fn(function() {
    this.attributes = {};
    this.navigate = jest.fn();
    this.reload = jest.fn();
    this.reset = jest.fn();
}));

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
const mockUserStoreGetPersistentSetting = jest.fn();
const mockUserStoreSetPersistentSetting = jest.fn();
const mockUserStoreHasSingleSignOn = jest.fn();
const mockUserStoreLoading = jest.fn();
const mockUserStoreLoginError = jest.fn();
const mockUserStoreLoginMethod = jest.fn();
const mockUserStoreForgotPasswordSuccess = jest.fn();
const mockUserStoreRedirectUrl = jest.fn();
const mockUserStoreLogout = jest.fn();

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

        get loading() {
            return mockUserStoreLoading();
        }

        get loginError() {
            return mockUserStoreLoginError();
        }

        get loginMethod() {
            return mockUserStoreLoginMethod();
        }

        get forgotPasswordSuccess() {
            return mockUserStoreForgotPasswordSuccess();
        }

        get redirectUrl() {
            return mockUserStoreRedirectUrl();
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

        setLoginError() {}

        setForgotPasswordSuccess() {}

        logout() {
            return mockUserStoreLogout();
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
    mockUserStoreHasSingleSignOn.mockReturnValue(false);
    mockUserStoreLoading.mockReturnValue(false);
    mockUserStoreLoginError.mockReturnValue(false);
    mockUserStoreLoginMethod.mockReturnValue('');
    mockUserStoreForgotPasswordSuccess.mockReturnValue(false);
    mockUserStoreRedirectUrl.mockReturnValue('');
    mockUserStoreLogout.mockReturnValue(Promise.resolve());
});

function createRouter(routeConfig) {
    const router = new Router({});

    if (routeConfig) {
        router.route = new Route(routeConfig);
    }

    return router;
}

function createRouteConfig() {
    return {
        name: 'test',
        path: '/webspaces',
        type: 'test',
    };
}

function renderApplication(props: Object = {}) {
    return render(
        <Application
            appVersion={props.appVersion === undefined ? null : props.appVersion}
            router={props.router || createRouter()}
            suluVersion="2.0.0-RC1"
        />
    );
}

test('Render login with loader', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(true);
    mockInitializedTranslationsLocale.mockReturnValue(null);
    mockUserStoreLoggedIn.mockReturnValue(false);

    renderApplication();

    expect(screen.getByLabelText('su-sulu')).toBeInTheDocument();
    expect(screen.queryByLabelText('sulu_admin.username_or_email')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.back_to_website')).not.toBeInTheDocument();
});

test('Render login screen to reset password', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(false);
    mockInitializedTranslationsLocale.mockReturnValue('en');
    mockUserStoreLoggedIn.mockReturnValue(false);

    const router = createRouter();
    router.attributes.forgotPasswordToken = 'some-uuid';

    renderApplication({router});

    expect(screen.getAllByText('sulu_admin.reset_password')).toHaveLength(2);
    expect(screen.getByLabelText('sulu_admin.password')).toBeInTheDocument();
    expect(screen.getByLabelText('sulu_admin.repeat_password')).toBeInTheDocument();
});

test('Render login when user is not logged in', () => {
    mockInitializerInitialized.mockReturnValue(false);
    mockInitializerLoading.mockReturnValue(false);
    mockInitializedTranslationsLocale.mockReturnValue('en');
    mockUserStoreLoggedIn.mockReturnValue(false);

    renderApplication();

    expect(screen.getByText('sulu_admin.welcome')).toBeInTheDocument();
    expect(screen.getByLabelText('sulu_admin.username_or_email')).toBeInTheDocument();
    expect(screen.getByLabelText('sulu_admin.password')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.login'})).toBeDisabled();
});

test('Should not fail if current route does not exist', () => {
    renderApplication();

    expect(screen.getByLabelText('su-sulu-logo')).toBeInTheDocument();
    expect(screen.getByText('Hikaru Sulu')).toBeInTheDocument();
    expect(screen.queryByRole('heading', {name: 'Test'})).not.toBeInTheDocument();
});

test('Render based on current route', () => {
    const router = createRouter(createRouteConfig());

    renderApplication({router});

    expect(screen.getByRole('heading', {name: 'Test'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'test'})).toBeInTheDocument();
    expect(screen.getByText('ProfileFormOverlay Mock')).toBeInTheDocument();
});

test('Render based on current route with app version', () => {
    const router = createRouter(createRouteConfig());

    renderApplication({appVersion: '666', router});

    expect(screen.getByRole('heading', {name: 'Test'})).toBeInTheDocument();
    expect(screen.getByLabelText('su-sulu-logo').parentElement).toHaveAttribute('title', '2.0.0-RC1');
});

test('Render opened navigation', async() => {
    const user = userEvent.setup();
    const router = createRouter(createRouteConfig());

    renderApplication({router});

    await user.click(screen.getByLabelText('su-bars'));

    expect(screen.queryByLabelText('su-bars')).not.toBeInTheDocument();
    expect(screen.getByTestId('backdrop')).toBeInTheDocument();
});

test('Pin navigation', async() => {
    const user = userEvent.setup();
    const router = createRouter(createRouteConfig());

    renderApplication({router});

    await user.click(screen.getByLabelText('su-bars'));
    await user.click(screen.getByLabelText('su-stick-right'));

    expect(screen.queryByTestId('backdrop')).not.toBeInTheDocument();
    expect(mockUserStoreSetPersistentSetting).toHaveBeenCalledWith('sulu_admin.application.navigation_pinned', true);
});

test('Pin navigation from beginning', () => {
    const router = createRouter(createRouteConfig());
    mockUserStoreGetPersistentSetting.mockReturnValue(true);

    renderApplication({router});

    expect(screen.queryByLabelText('su-bars')).not.toBeInTheDocument();
    expect(screen.queryByTestId('backdrop')).not.toBeInTheDocument();
    expect(mockUserStoreGetPersistentSetting).toHaveBeenCalledWith('sulu_admin.application.navigation_pinned');
});

test('Do not pin navigation from beginning', () => {
    const router = createRouter(createRouteConfig());
    mockUserStoreGetPersistentSetting.mockReturnValue(false);

    renderApplication({router});

    expect(screen.getByLabelText('su-bars')).toBeInTheDocument();
    expect(screen.queryByTestId('backdrop')).not.toBeInTheDocument();
    expect(mockUserStoreGetPersistentSetting).toHaveBeenCalledWith('sulu_admin.application.navigation_pinned');
});
