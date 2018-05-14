// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import Login from '../Login';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn(function(key) {
        return key;
    }),
}));

test('Should render the component with loader', () => {
    expect(render(
        <Login onClearError={jest.fn()} onLogin={jest.fn()} onResetPassword={jest.fn()} />)
    ).toMatchSnapshot();
});

test('Should render the Login component', () => {
    expect(render(
        <Login initialized={true} onClearError={jest.fn()} onLogin={jest.fn()} onResetPassword={jest.fn()} />)
    ).toMatchSnapshot();
});

test('Should render the LoginForm component with error', () => {
    expect(render(
        <Login
            initialized={true}
            loginError={true}
            onClearError={jest.fn()}
            onLogin={jest.fn()}
            onResetPassword={jest.fn()}
        />
    )).toMatchSnapshot();
});

test('Should render the Login with reset password view', () => {
    const loginForm = shallow(
        <Login initialized={true} onClearError={jest.fn()} onLogin={jest.fn()} onResetPassword={jest.fn()} />
    );
    loginForm.instance().handleChangeToResetForm();

    expect(loginForm.render()).toMatchSnapshot();
});

test('Should render the Login with reset password with success', () => {
    const loginForm = shallow(
        <Login
            resetSuccess={true}
            initialized={true}
            onClearError={jest.fn()}
            onLogin={jest.fn()}
            onResetPassword={jest.fn()}
        />
    );
    loginForm.instance().handleChangeToResetForm();

    expect(loginForm.render()).toMatchSnapshot();
});

test('Should call the submit handler of the current view', () => {
    const loginSpy = jest.fn();
    const resetPasswordSpy = jest.fn();
    const eventMock = {preventDefault: () => {}};
    const login = shallow(
        <Login initialized={true} onClearError={jest.fn()} onLogin={loginSpy} onResetPassword={resetPasswordSpy} />
    );
    const loginInstance = login.instance();

    loginInstance.handleUserChange('testUser');
    loginInstance.handlePasswordChange('testPassword');

    loginInstance.handleLoginFormSubmit(eventMock);
    expect(loginSpy).toBeCalledWith('testUser', 'testPassword');

    loginInstance.handleChangeToResetForm();
    loginInstance.handleResetFormSubmit(eventMock);
    expect(resetPasswordSpy).toBeCalledWith('testUser');
});

test('Should call the onClearError handler when user is changed', () => {
    const onClearError = jest.fn();
    const login = mount(
        <Login
            initialized={true}
            onClearError={onClearError}
            onLogin={jest.fn()}
            onResetPassword={jest.fn()}
        />
    );

    expect(login.instance().user).toBeUndefined();
    login.find('LoginForm').prop('onUserChange')('testi');

    expect(onClearError).toBeCalled();
    expect(login.instance().user).toBe('testi');

    // switch to reset form and the onUserChange should trigger the same
    login.instance().handleChangeToResetForm();
    login.update();

    login.find('ResetForm').prop('onUserChange')('testi-forgotten');
    expect(onClearError).toBeCalled();
    expect(login.instance().user).toBe('testi-forgotten');
});

test('Should call the onClearError handler when password is changed', () => {
    const onClearError = jest.fn();
    const login = mount(
        <Login
            initialized={true}
            onClearError={onClearError}
            onLogin={jest.fn()}
            onResetPassword={jest.fn()}
        />
    );

    expect(login.instance().password).toBeUndefined();
    login.find('LoginForm').prop('onPasswordChange')('no-pw');

    expect(onClearError).toBeCalled();
    expect(login.instance().password).toBe('no-pw');
});
