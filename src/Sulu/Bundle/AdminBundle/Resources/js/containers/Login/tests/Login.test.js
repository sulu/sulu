// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Router from '../../../services/Router';
import userStore from '../../../stores/userStore';
import Login from '../Login';

jest.mock('../../../services/Router', () => jest.fn(function() {
    this.attributes = {};
    this.reset = jest.fn();
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn(function(key) {
        return key;
    }),
}));

const mockUserStoreLogin = jest.fn().mockReturnValue(Promise.resolve({}));
const mockUserStoreClear = jest.fn();
const mockUserStoreForgotPassword = jest.fn();
const mockUserStoreResetPassword = jest.fn().mockReturnValue(Promise.resolve({}));
const mockUserStoreLoginError = jest.fn();
const mockUserStoreSetResetSuccess = jest.fn();
const mockUserStoreLoading = jest.fn().mockReturnValue(false);
const mockUserStoreForgotPasswordSuccess = jest.fn().mockReturnValue(false);

jest.mock('../../../stores/userStore', () => {
    return new class {
        clear() {
            return mockUserStoreClear();
        }

        login(user, password) {
            return mockUserStoreLogin(user, password);
        }

        forgotPassword(user) {
            return mockUserStoreForgotPassword(user);
        }

        resetPassword(password) {
            return mockUserStoreResetPassword(password);
        }

        setLoginError(value) {
            return mockUserStoreLoginError(value);
        }

        setResetSuccess(value) {
            return mockUserStoreSetResetSuccess(value);
        }

        get loading() {
            return mockUserStoreLoading();
        }

        get forgotPasswordSuccess() {
            return mockUserStoreForgotPasswordSuccess();
        }
    };
});

beforeEach(() => {
    userStore.clear();
});

test('Should render the Login component when initialized is true', () => {
    const router = new Router();

    expect(render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />)
    ).toMatchSnapshot();
});

test('Should render the component with loader', () => {
    const router = new Router();

    expect(render(
        <Login initialized={false} onLoginSuccess={jest.fn()} router={router} />)
    ).toMatchSnapshot();
});

test('Should render the LoginForm component with error', () => {
    const router = new Router();

    mockUserStoreLoading.mockReturnValueOnce(true);
    expect(render(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    )).toMatchSnapshot();
});

test('Should render the Login with forgot password view', () => {
    const router = new Router();

    const loginForm = mount(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );
    loginForm.instance().handleChangeToForgotPasswordForm();

    expect(loginForm.render()).toMatchSnapshot();
});

test('Should render the Login with forgot password with success', () => {
    const router = new Router();

    mockUserStoreForgotPasswordSuccess.mockReturnValueOnce(true);
    const loginForm = shallow(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );
    loginForm.instance().handleChangeToForgotPasswordForm();

    expect(loginForm.render()).toMatchSnapshot();
});

test('Should render the Login with reset password view', () => {
    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    const loginForm = mount(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    expect(loginForm.render()).toMatchSnapshot();
});

test('Should call the submit handler of the login view', () => {
    const router = new Router();

    const eventMock = {preventDefault: () => {}};
    const login = mount(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    login.find('Input[icon="su-user"]').prop('onChange')('testUser');
    login.find('Input[icon="su-lock"]').prop('onChange')('testPassword');

    login.find('form').prop('onSubmit')(eventMock);

    expect(mockUserStoreLogin).toBeCalledWith('testUser', 'testPassword');
});

test('Should call the submit handler of the forgot password view', () => {
    const router = new Router();

    const eventMock = {preventDefault: () => {}};
    const login = mount(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    login.find('Button[children="sulu_admin.forgot_password"]').prop('onClick')();

    login.update();
    login.find('Input[icon="su-user"]').prop('onChange')('testUser');
    login.find('form').prop('onSubmit')(eventMock);

    expect(mockUserStoreForgotPassword).toBeCalledWith('testUser');
});

test('Should call the submit handler of the reset password view', () => {
    const promise = Promise.resolve();
    mockUserStoreResetPassword.mockReturnValue(promise);

    const router = new Router();
    router.attributes.forgotPasswordToken = 'some-uuid';

    const eventMock = {preventDefault: () => {}};
    const login = mount(
        <Login initialized={true} onLoginSuccess={jest.fn()} router={router} />
    );

    login.find('Input[icon="su-lock"]').at(0).prop('onChange')('test');
    login.find('Input[icon="su-lock"]').at(1).prop('onChange')('test');
    login.find('form').prop('onSubmit')(eventMock);

    expect(mockUserStoreResetPassword).toBeCalledWith('test');

    return promise.then(() => {
        expect(router.reset).toBeCalled();
    });
});
