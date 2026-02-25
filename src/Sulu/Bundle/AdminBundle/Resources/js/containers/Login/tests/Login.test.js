// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Router from '../../../services/Router';
import userStore from '../../../stores/userStore';
import Login from '../Login';

jest.mock('../../../services/Router/Router', () => jest.fn(function() {
    this.attributes = {};
    this.reset = jest.fn();
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const mockUserStoreLogin = jest.fn().mockReturnValue(Promise.resolve({}));
const mockUserStoreTwoFactorLogin = jest.fn().mockReturnValue(Promise.resolve({}));
const mockUserStoreClear = jest.fn();
const mockUserStoreForgotPassword = jest.fn().mockReturnValue(Promise.resolve({}));
const mockUserStoreResetPassword = jest.fn().mockReturnValue(Promise.resolve({}));
const mockUserStoreLoginError = jest.fn();
const mockUserStoreTwoFactorMethods = jest.fn();
const mockUserStoreTwoFactorError = jest.fn();
const mockUserStoreSetResetSuccess = jest.fn();
const mockUserStoreLoading = jest.fn().mockReturnValue(false);
const mockUserStoreForgotPasswordSuccess = jest.fn().mockReturnValue(false);
const mockUserStoreLoginMethod = jest.fn().mockReturnValue(false);
const mockUserStoreHasSingleSignOn = jest.fn().mockReturnValue(false);
const mockUserStoreRedirectUrl = jest.fn().mockReturnValue('');

jest.mock('../../../stores/userStore', () => {
    return new class {
        clear() {
            return mockUserStoreClear();
        }

        login(data) {
            return mockUserStoreLogin(data);
        }

        twoFactorLogin(data) {
            return mockUserStoreTwoFactorLogin(data);
        }

        forgotPassword(data) {
            return mockUserStoreForgotPassword(data);
        }

        resetPassword(data) {
            return mockUserStoreResetPassword(data);
        }

        setTwoFactorMethods(data) {
            return mockUserStoreTwoFactorMethods(data);
        }

        setTwoFactorError(data) {
            return mockUserStoreTwoFactorError(data);
        }

        setLoginError(value) {
            return mockUserStoreLoginError(value);
        }

        setResetSuccess(value) {
            return mockUserStoreSetResetSuccess(value);
        }

        get loginMethod() {
            return mockUserStoreLoginMethod();
        }

        hasSingleSignOn() {
            return mockUserStoreHasSingleSignOn();
        }

        get redirectUrl() {
            return mockUserStoreRedirectUrl();
        }

        get loading() {
            return mockUserStoreLoading();
        }

        get forgotPasswordSuccess() {
            return mockUserStoreForgotPasswordSuccess();
        }

        validatePassword(password: string): boolean {
            return (new RegExp('.{6,}')).test(password);
        }
    };
});

jest.mock('../../../stores', () => ({
    userStore: jest.requireMock('../../../stores/userStore'),
}));

beforeEach(() => {
    jest.clearAllMocks();

    mockUserStoreLogin.mockReturnValue(Promise.resolve({}));
    mockUserStoreTwoFactorLogin.mockReturnValue(Promise.resolve({}));
    mockUserStoreForgotPassword.mockReturnValue(Promise.resolve({}));
    mockUserStoreResetPassword.mockReturnValue(Promise.resolve({}));
    mockUserStoreLoading.mockReturnValue(false);
    mockUserStoreForgotPasswordSuccess.mockReturnValue(false);
    mockUserStoreLoginMethod.mockReturnValue(false);
    mockUserStoreHasSingleSignOn.mockReturnValue(false);
    mockUserStoreRedirectUrl.mockReturnValue('');

    userStore.clear();
});

test('Should render the Login component when initialized is true', () => {
    const router = new Router();

    const {asFragment} = render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the component with loader', () => {
    const router = new Router();

    const {asFragment} = render(
        <Login initialized={false} onLoginSuccess={jest.fn()} router={router} />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the LoginForm component with loading state', async() => {
    const router = new Router();
    const user = userEvent.setup();

    mockUserStoreLoading.mockReturnValueOnce(true);
    mockUserStoreHasSingleSignOn.mockReturnValueOnce(true);
    mockUserStoreLoginMethod.mockReturnValueOnce('');

    render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'testUser');

    expect(screen.getByRole('button', {name: 'sulu_admin.login'})).toBeDisabled();
});

test('Should render the Login with forgot password view', async() => {
    const router = new Router();
    const user = userEvent.setup();

    const {asFragment} = render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.forgot_password'}));
    expect(asFragment()).toMatchSnapshot();
});

test('Should render the Login with forgot password with success', async() => {
    const router = new Router();
    const user = userEvent.setup();

    mockUserStoreForgotPasswordSuccess.mockReturnValueOnce(true);

    const {asFragment} = render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.forgot_password'}));

    expect(asFragment()).toMatchSnapshot();
    expect(screen.getByText('sulu_admin.forgot_password_success')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.reset_resend'})).toBeInTheDocument();
});

test('Should render the Login with reset password view', () => {
    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    const {asFragment} = render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should call the submit handler of the login view', async() => {
    const router = new Router();
    const user = userEvent.setup();

    render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'testUser');
    await user.type(screen.getByLabelText('sulu_admin.password'), 'testPassword');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.login'}));

    expect(mockUserStoreLogin).toHaveBeenCalledWith({password: 'testPassword', username: 'testUser'});
});

test('Should call the submit handler of the forgot password view', async() => {
    const router = new Router();
    const user = userEvent.setup();

    render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.forgot_password'}));
    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'testUser');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset'}));

    expect(mockUserStoreForgotPassword).toHaveBeenCalledWith({user: 'testUser'});
});

test('Should call the submit handler of the reset password view', async() => {
    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    const onLoginSuccess = jest.fn();
    const user = userEvent.setup();

    render(
        <Login initialized={true} onLoginSuccess={onLoginSuccess} router={router} />
    );

    await user.type(screen.getByLabelText('sulu_admin.password'), 'testpassword');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'testpassword');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(mockUserStoreResetPassword).toHaveBeenCalledWith({
        password: 'testpassword',
        token: 'some-uuid',
    });

    await waitFor(() => expect(router.reset).toHaveBeenCalled());
    await waitFor(() => expect(onLoginSuccess).toHaveBeenCalled());
});

test('Should not call the submit handler of the reset password view with an invalid password', async() => {
    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    const user = userEvent.setup();

    render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    await user.type(screen.getByLabelText('sulu_admin.password'), 'short');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'short');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(mockUserStoreResetPassword).not.toHaveBeenCalled();
    expect(router.reset).not.toHaveBeenCalled();
});

test('Should not call the submit handler of the reset password view with not matching passwords', async() => {
    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    const user = userEvent.setup();

    render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    await user.type(screen.getByLabelText('sulu_admin.password'), 'testpassword');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'mismatchpassword');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(mockUserStoreResetPassword).not.toHaveBeenCalled();
    expect(router.reset).not.toHaveBeenCalled();
});

test('Should render the Login with only username/email', () => {
    const router = new Router();
    mockUserStoreHasSingleSignOn.mockReturnValue(true);
    mockUserStoreLoginMethod.mockReturnValueOnce('');

    const {asFragment} = render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    expect(asFragment()).toMatchSnapshot();
    expect(screen.getByLabelText('sulu_admin.username_or_email')).toBeInTheDocument();
    expect(screen.queryByLabelText('sulu_admin.password')).not.toBeInTheDocument();
});

test('Should render the Login with only password', () => {
    const router = new Router();
    mockUserStoreHasSingleSignOn.mockReturnValue(true);
    mockUserStoreLoginMethod.mockReturnValue('json_login');

    const {asFragment} = render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    expect(asFragment()).toMatchSnapshot();
    expect(screen.queryByLabelText('sulu_admin.username_or_email')).not.toBeInTheDocument();
    expect(screen.getByLabelText('sulu_admin.password')).toBeInTheDocument();
});
