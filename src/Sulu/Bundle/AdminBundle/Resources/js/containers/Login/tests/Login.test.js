// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Login from '../Login';
import userStore from '../../../stores/UserStore';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn(function(key) {
        return key;
    }),
}));

const mockUserStoreLogin = jest.fn().mockReturnValue(Promise.resolve({}));
const mockUserStoreClear = jest.fn();
const mockUserStoreResetPassword = jest.fn();
const mockUserStoreLoginError = jest.fn();
const mockUserStoreSetResetSuccess = jest.fn();
const mockUserStoreLoading = jest.fn().mockReturnValue(false);
const mockUserStoreResetSuccess = jest.fn().mockReturnValue(false);

jest.mock('../../../stores/UserStore', () => {
    return new class {
        clear() {
            return mockUserStoreClear();
        }

        login(user, password) {
            return mockUserStoreLogin(user, password);
        }

        resetPassword(user) {
            return mockUserStoreResetPassword(user);
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

        get resetSuccess() {
            return mockUserStoreResetSuccess();
        }
    };
});

beforeEach(() => {
    userStore.clear();
});

test('Should render the Login component when initialized is true', () => {
    expect(render(
        <Login initialized={true} onLoginSuccess={jest.fn()} />)
    ).toMatchSnapshot();
});

test('Should render the component with loader', () => {
    expect(render(
        <Login initialized={false} onLoginSuccess={jest.fn()} />)
    ).toMatchSnapshot();
});

test('Should render the LoginForm component with error', () => {
    mockUserStoreLoading.mockReturnValueOnce(true);
    expect(render(
        <Login initialized={true} onLoginSuccess={jest.fn()} />
    )).toMatchSnapshot();
});

test('Should render the Login with reset password view', () => {
    const loginForm = shallow(
        <Login initialized={true} onLoginSuccess={jest.fn()} />
    );
    loginForm.instance().handleChangeToResetForm();

    expect(loginForm.render()).toMatchSnapshot();
});

test('Should render the Login with reset password with success', () => {
    mockUserStoreResetSuccess.mockReturnValueOnce(true);
    const loginForm = shallow(
        <Login initialized={true} onLoginSuccess={jest.fn()} />
    );
    loginForm.instance().handleChangeToResetForm();

    expect(loginForm.render()).toMatchSnapshot();
});

test('Should call the submit handler of the current view', () => {
    const eventMock = {preventDefault: () => {}};
    const login = shallow(
        <Login initialized={true} onLoginSuccess={jest.fn()} />
    );
    const loginInstance = login.instance();

    loginInstance.handleUserChange('testUser');
    loginInstance.handlePasswordChange('testPassword');

    loginInstance.handleLoginFormSubmit(eventMock);
    expect(mockUserStoreLogin).toBeCalledWith('testUser', 'testPassword');

    loginInstance.handleChangeToResetForm();
    loginInstance.handleResetFormSubmit(eventMock);
    expect(mockUserStoreResetPassword).toBeCalledWith('testUser');
});

test('Should clear the state when user is changed', () => {
    const login = mount(
        <Login initialized={true} onLoginSuccess={jest.fn()} />
    );

    expect(login.instance().user).toBeUndefined();
    login.find('LoginForm').prop('onUserChange')('testi');

    expect(mockUserStoreLoginError).toBeCalledWith(false);
    expect(login.instance().user).toBe('testi');

    // switch to reset form and the onUserChange should trigger the same
    login.instance().handleChangeToResetForm();
    login.update();

    login.find('ResetForm').prop('onUserChange')('testi-forgotten');
    expect(mockUserStoreSetResetSuccess).toBeCalledWith(false);
    expect(login.instance().user).toBe('testi-forgotten');
});

test('Should call the onClearError handler when password is changed', () => {
    const login = mount(
        <Login initialized={true} onLoginSuccess={jest.fn()} />
    );

    expect(login.instance().password).toBeUndefined();
    login.find('LoginForm').prop('onPasswordChange')('no-pw');

    expect(mockUserStoreLoginError).toBeCalledWith(false);
    expect(login.instance().password).toBe('no-pw');
});
