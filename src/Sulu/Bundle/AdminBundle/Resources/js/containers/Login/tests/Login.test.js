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

jest.mock('../../../utils/Translator');

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
const mockUserStoreSetLoginMethod = jest.fn();
const mockUserStoreGetTwoFactorMethods = jest.fn().mockReturnValue([]);
const mockUserStoreHasSingleSignOn = jest.fn();
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

        setLoginMethod(value) {
            return mockUserStoreSetLoginMethod(value);
        }

        get twoFactorMethods() {
            return mockUserStoreGetTwoFactorMethods();
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
    mockUserStoreLoading.mockReturnValue(false);
    mockUserStoreForgotPasswordSuccess.mockReturnValue(false);
    mockUserStoreLoginMethod.mockReturnValue(false);
    mockUserStoreHasSingleSignOn.mockReturnValue(false);
    mockUserStoreRedirectUrl.mockReturnValue('');
    userStore.clear();
});

test('Should render the Login component when initialized is true', () => {
    const router = new Router();

    const {asFragment} = render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the component with loader', () => {
    const router = new Router();

    const {asFragment} = render(<Login initialized={false} onLoginSuccess={jest.fn()} router={router} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the LoginForm component with error', () => {
    const router = new Router();

    mockUserStoreLoading.mockReturnValueOnce(true);
    const {asFragment} = render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the Login with forgot password view', async() => {
    const user = userEvent.setup();
    const router = new Router();

    const {asFragment} = render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.forgot_password'}));

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the Login with forgot password with success', async() => {
    const user = userEvent.setup();
    const router = new Router();

    mockUserStoreForgotPasswordSuccess.mockReturnValueOnce(true);
    const {asFragment} = render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.forgot_password'}));

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the Login with reset password view', () => {
    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    const {asFragment} = render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Should call the submit handler of the login view', async() => {
    const user = userEvent.setup();
    const router = new Router();

    render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'testUser');
    await user.type(screen.getByLabelText('sulu_admin.password'), 'testPassword');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.login'}));

    expect(mockUserStoreLogin).toHaveBeenCalledWith({username: 'testUser', password: 'testPassword'});
});

test('Should call the submit handler of the forgot password view', async() => {
    const user = userEvent.setup();
    const router = new Router();

    render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.forgot_password'}));
    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'testUser');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset'}));

    expect(mockUserStoreForgotPassword).toHaveBeenCalledWith({user: 'testUser'});
});

test('Should call the submit handler of the reset password view', async() => {
    const user = userEvent.setup();
    const promise = Promise.resolve();
    mockUserStoreResetPassword.mockReturnValue(promise);

    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    await user.type(screen.getByLabelText('sulu_admin.password'), 'testpassword');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'testpassword');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(mockUserStoreResetPassword).toHaveBeenCalledWith({
        password: 'testpassword',
        token: 'some-uuid',
    });

    await waitFor(() => expect(router.reset).toHaveBeenCalled());
});

test('Should not call the submit handler of the reset password view with an invalid password', async() => {
    const user = userEvent.setup();
    const promise = Promise.resolve();
    mockUserStoreResetPassword.mockReturnValue(promise);

    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    await user.type(screen.getByLabelText('sulu_admin.password'), 'test');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'test');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(mockUserStoreResetPassword).not.toHaveBeenCalled();

    await promise;

    expect(router.reset).not.toHaveBeenCalled();
});

test('Should not call the submit handler of the reset password view with not matching passwords', async() => {
    const user = userEvent.setup();
    const promise = Promise.resolve();
    mockUserStoreResetPassword.mockReturnValue(promise);

    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    await user.type(screen.getByLabelText('sulu_admin.password'), 'test');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'testpassword');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(mockUserStoreResetPassword).not.toHaveBeenCalled();

    await promise;

    expect(router.reset).not.toHaveBeenCalled();
});

test('Should render the Login with only username/email', () => {
    const router = new Router();
    mockUserStoreHasSingleSignOn.mockReturnValue(true);
    mockUserStoreLoginMethod.mockReturnValueOnce('');

    const {asFragment} = render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the Login with only password', () => {
    const router = new Router();
    mockUserStoreHasSingleSignOn.mockReturnValue(true);
    mockUserStoreLoginMethod.mockReturnValue('json_login');

    const {asFragment} = render(<Login initialized={true} onLoginSuccess={jest.fn()} router={router} />);

    expect(asFragment()).toMatchSnapshot();
});

test('Should stay on the login form after the username step with single sign on', () => {
    const router = new Router();
    const loginSuccessSpy = jest.fn();
    mockUserStoreHasSingleSignOn.mockReturnValue(true);
    mockUserStoreLoginMethod.mockReturnValue('json_login');
    mockUserStoreGetTwoFactorMethods.mockReturnValue([]);

    const login = mount(
        <Login initialized={true} onLoginSuccess={loginSuccessSpy} router={router} />
    );

    login.find('LoginForm').prop('onSubmit')({username: 'testUser', password: ''});

    return Promise.resolve().then(() => {
        login.update();
        expect(login.find('TwoFactorForm')).toHaveLength(0);
        expect(login.find('LoginForm')).toHaveLength(1);
        expect(loginSuccessSpy).not.toHaveBeenCalled();
    });
});

test('Should show the two factor form after the password step with single sign on', () => {
    const router = new Router();
    const loginSuccessSpy = jest.fn();
    mockUserStoreHasSingleSignOn.mockReturnValue(true);
    mockUserStoreLoginMethod.mockReturnValue('json_login');
    mockUserStoreGetTwoFactorMethods.mockReturnValue(['email']);

    const login = mount(
        <Login initialized={true} onLoginSuccess={loginSuccessSpy} router={router} />
    );

    login.find('LoginForm').prop('onSubmit')({username: 'testUser', password: 'testPassword'});

    return Promise.resolve().then(() => {
        login.update();
        expect(login.find('TwoFactorForm')).toHaveLength(1);
        expect(loginSuccessSpy).not.toHaveBeenCalled();
    });
});

test('Should reset the login method when going back to the login form', () => {
    const router = new Router();
    mockUserStoreHasSingleSignOn.mockReturnValue(true);
    mockUserStoreLoginMethod.mockReturnValue('json_login');
    mockUserStoreGetTwoFactorMethods.mockReturnValue(['email']);

    const login = mount(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    login.find('LoginForm').prop('onSubmit')({username: 'testUser', password: 'testPassword'});

    return Promise.resolve().then(() => {
        login.update();
        login.find('TwoFactorForm').prop('onChangeForm')();
        login.update();

        expect(mockUserStoreSetLoginMethod).toHaveBeenCalledWith('');
        expect(login.find('LoginForm')).toHaveLength(1);
    });
});
