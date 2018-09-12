// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import LoginForm from '../LoginForm';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn(function(key) {
        return key;
    }),
}));

test('Should render the component', () => {
    expect(render(
        <LoginForm
            onChangeForm={jest.fn()}
            onPasswordChange={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
            password={undefined}
            user={undefined}
        />)
    ).toMatchSnapshot();
});

test('Should render the component with data', () => {
    expect(render(
        <LoginForm
            onChangeForm={jest.fn()}
            onPasswordChange={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
            password="test"
            user="test"
        />)
    ).toMatchSnapshot();
});

test('Should render the component loading', () => {
    expect(render(
        <LoginForm
            error={true}
            onChangeForm={jest.fn()}
            onPasswordChange={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
            password="test"
            user="test"
        />)
    ).toMatchSnapshot();
});

test('Should render the component with error', () => {
    expect(render(
        <LoginForm
            error={true}
            onChangeForm={jest.fn()}
            onPasswordChange={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
            password="test"
            user="test"
        />)
    ).toMatchSnapshot();
});

test('Should trigger onUserChange correctly', () => {
    const onUserChange = jest.fn();
    const loginForm = shallow(
        <LoginForm
            onChangeForm={jest.fn()}
            onPasswordChange={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={onUserChange}
            password="test"
            user="test"
        />
    );

    loginForm.find('Input').at(0).simulate('change', 'test-user-123');

    expect(onUserChange).toBeCalledWith('test-user-123');
});

test('Should trigger onPasswordChange correctly', () => {
    const onPasswordChange = jest.fn();
    const loginForm = shallow(
        <LoginForm
            onChangeForm={jest.fn()}
            onPasswordChange={onPasswordChange}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
            password="test"
            user="test"
        />
    );

    loginForm.find('Input').at(1).simulate('change', '123');

    expect(onPasswordChange).toBeCalledWith('123');
});

test('Should trigger onChangeForm correctly', () => {
    const onChangeForm = jest.fn();
    const loginForm = shallow(
        <LoginForm
            onChangeForm={onChangeForm}
            onPasswordChange={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
            password="test"
            user="test"
        />
    );

    loginForm.find('Button').at(0).simulate('click');

    expect(onChangeForm).toBeCalled();
});

test('Should trigger onSubmit correctly', () => {
    const onSubmit = jest.fn();
    const loginForm = shallow(
        <LoginForm
            onChangeForm={jest.fn()}
            onPasswordChange={jest.fn()}
            onSubmit={onSubmit}
            onUserChange={jest.fn()}
            password="test"
            user="test"
        />
    );
    loginForm.find('form').simulate('submit');

    expect(onSubmit).toBeCalled();
});
