// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import LoginForm from '../LoginForm';

jest.mock('../../../utils', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.password':
                return 'password';
            case 'sulu_admin.forgot_password':
                return 'Forgot password';
            case 'sulu_admin.login':
                return 'Login';
            case 'sulu_admin.username_or_email':
                return 'Username or Email';
            case 'sulu_admin.to_login':
                return 'To login';
            case 'sulu_admin.reset':
                return 'Reset';
        }
    },
}));

test('Should render the LoginForm component', () => {
    expect(render(<LoginForm onLogin={jest.fn()} onResetPassword={jest.fn()} />)).toMatchSnapshot();
});

test('Should render the LoginForm with reset password view', () => {
    const loginForm = shallow(<LoginForm onLogin={jest.fn()} onResetPassword={jest.fn()} />);
    loginForm.instance().handleChangeToResetForm();

    expect(loginForm.render()).toMatchSnapshot();
});

test('Should call the submit handler of the current view', () => {
    const loginSpy = jest.fn();
    const resetPasswordSpy = jest.fn();
    const eventMock = {preventDefault: () => {}};
    const loginForm = shallow(<LoginForm onLogin={loginSpy} onResetPassword={resetPasswordSpy} />);
    const loginFormInstance = loginForm.instance();

    loginFormInstance.handleUserChange('testUser');
    loginFormInstance.handlePasswordChange('testPassword');

    loginFormInstance.handleLoginFormSubmit(eventMock);
    expect(loginSpy).toBeCalledWith('testUser', 'testPassword');

    loginFormInstance.handleChangeToResetForm();
    loginFormInstance.handleResetFormSubmit(eventMock);
    expect(resetPasswordSpy).toBeCalledWith('testUser');
});
