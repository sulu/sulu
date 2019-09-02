// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Login from '../Login';
import userStore from '../../../stores/userStore';

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

jest.mock('../../../stores/userStore', () => {
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
    const login = mount(
        <Login initialized={true} onLoginSuccess={jest.fn()} />
    );

    login.find('Input[icon="su-user"]').prop('onChange')('testUser');
    login.find('Input[icon="su-lock"]').prop('onChange')('testPassword');

    login.find('form').prop('onSubmit')(eventMock);

    expect(mockUserStoreLogin).toBeCalledWith('testUser', 'testPassword');

    login.find('Button[children="sulu_admin.forgot_password"]').prop('onClick')();

    login.update();
    login.find('Input[icon="su-user"]').prop('onChange')('testUser');
    login.find('form').prop('onSubmit')(eventMock);

    expect(mockUserStoreResetPassword).toBeCalledWith('testUser');
});
