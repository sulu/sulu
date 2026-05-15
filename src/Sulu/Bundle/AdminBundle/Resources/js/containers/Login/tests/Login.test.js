// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Router from '../../../services/Router';
import userStore from '../../../stores/userStore';
import Login from '../Login';

jest.mock('../../../services/Router/Router', () => jest.fn(function() {
    this.attributes = {};
    this.reset = jest.fn();
}));

const mockUserStoreLogin = jest.fn().mockResolvedValue({});
const mockUserStoreTwoFactorLogin = jest.fn().mockResolvedValue({});
const mockUserStoreClear = jest.fn();
const mockUserStoreForgotPassword = jest.fn().mockResolvedValue({});
const mockUserStoreResetPassword = jest.fn().mockResolvedValue({});
const mockUserStoreSetLoginError = jest.fn();
const mockUserStoreGetLoginError = jest.fn().mockReturnValue(false);
const mockUserStoreSetTwoFactorMethods = jest.fn();
const mockUserStoreGetTwoFactorMethods = jest.fn().mockReturnValue([]);
const mockUserStoreSetTwoFactorError = jest.fn();
const mockUserStoreGetTwoFactorError = jest.fn().mockReturnValue(false);
const mockUserStoreSetResetSuccess = jest.fn();
const mockUserStoreSetForgotPasswordSuccess = jest.fn();
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
            return mockUserStoreSetTwoFactorMethods(data);
        }

        setTwoFactorError(data) {
            return mockUserStoreSetTwoFactorError(data);
        }

        setLoginError(value) {
            return mockUserStoreSetLoginError(value);
        }

        setResetSuccess(value) {
            return mockUserStoreSetResetSuccess(value);
        }

        setForgotPasswordSuccess(value) {
            return mockUserStoreSetForgotPasswordSuccess(value);
        }

        get loginMethod() {
            return mockUserStoreLoginMethod();
        }

        get loginError() {
            return mockUserStoreGetLoginError();
        }

        get twoFactorMethods() {
            return mockUserStoreGetTwoFactorMethods();
        }

        get twoFactorError() {
            return mockUserStoreGetTwoFactorError();
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

beforeEach(() => {
    jest.clearAllMocks();

    mockUserStoreLoading.mockReturnValue(false);
    mockUserStoreForgotPasswordSuccess.mockReturnValue(false);
    mockUserStoreLoginMethod.mockReturnValue(false);
    mockUserStoreHasSingleSignOn.mockReturnValue(false);
    mockUserStoreRedirectUrl.mockReturnValue('');
    mockUserStoreGetLoginError.mockReturnValue(false);
    mockUserStoreGetTwoFactorMethods.mockReturnValue([]);
    mockUserStoreGetTwoFactorError.mockReturnValue(false);

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

test('Should render the LoginForm component with error', () => {
    const router = new Router();

    mockUserStoreLoading.mockReturnValueOnce(true);
    const {asFragment} = render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the Login with forgot password view', async() => {
    const user = userEvent.setup();
    const router = new Router();

    const {asFragment} = render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.forgot_password'}));

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the Login with forgot password with success', async() => {
    const user = userEvent.setup();
    const router = new Router();

    mockUserStoreForgotPasswordSuccess.mockReturnValueOnce(true);
    const {asFragment} = render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.forgot_password'}));

    expect(asFragment()).toMatchSnapshot();
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
    const user = userEvent.setup();
    const router = new Router();
    render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    const [usernameInput] = screen.getAllByRole('textbox');
    const passwordInput = screen.getByLabelText('sulu_admin.password');

    await user.type(usernameInput, 'testUser');
    await user.type(passwordInput, 'testPassword');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.login'}));

    expect(mockUserStoreLogin).toBeCalledWith({username: 'testUser', password: 'testPassword'});
});

test('Should call the submit handler of the forgot password view', async() => {
    const user = userEvent.setup();
    const router = new Router();
    render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.forgot_password'}));

    const [usernameInput] = screen.getAllByRole('textbox');
    await user.type(usernameInput, 'testUser');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset'}));

    expect(mockUserStoreForgotPassword).toBeCalledWith({user: 'testUser'});
});

test('Should call the submit handler of the reset password view', async() => {
    const user = userEvent.setup();
    const promise = Promise.resolve();
    mockUserStoreResetPassword.mockReturnValue(promise);

    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    const passwordInputs = screen.getAllByLabelText('sulu_admin.password');
    await user.type(passwordInputs[0], 'testpassword');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'testpassword');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(mockUserStoreResetPassword).toBeCalledWith({
        password: 'testpassword',
        token: 'some-uuid',
    });

    await promise;
    expect(router.reset).toBeCalled();
});

test('Should not call the submit handler of the reset password view with an invalid password', async() => {
    const user = userEvent.setup();
    const promise = Promise.resolve();
    mockUserStoreResetPassword.mockReturnValue(promise);

    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    const passwordInputs = screen.getAllByLabelText('sulu_admin.password');
    await user.type(passwordInputs[0], 'test');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'test');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(mockUserStoreResetPassword).not.toBeCalled();
});

test('Should not call the submit handler of the reset password view with not matching passwords', async() => {
    const user = userEvent.setup();
    const promise = Promise.resolve();
    mockUserStoreResetPassword.mockReturnValue(promise);

    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    const passwordInputs = screen.getAllByLabelText('sulu_admin.password');
    await user.type(passwordInputs[0], 'test');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'testpassword');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(mockUserStoreResetPassword).not.toBeCalled();
});

test('Should render the Login with only username/email', () => {
    const router = new Router();
    mockUserStoreHasSingleSignOn.mockReturnValue(true);
    mockUserStoreLoginMethod.mockReturnValueOnce('');

    const {asFragment} = render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the Login with only password', () => {
    const router = new Router();
    mockUserStoreHasSingleSignOn.mockReturnValue(true);
    mockUserStoreLoginMethod.mockReturnValue('json_login');

    const {asFragment} = render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    expect(asFragment()).toMatchSnapshot();
});
