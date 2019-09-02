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
            onSubmit={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should render the component loading', () => {
    expect(render(
        <LoginForm
            loading={true}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should render the component with error', () => {
    expect(render(
        <LoginForm
            error={true}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should trigger onChangeForm correctly', () => {
    const onChangeForm = jest.fn();
    const loginForm = shallow(
        <LoginForm
            onChangeForm={onChangeForm}
            onSubmit={jest.fn()}
        />
    );

    loginForm.find('Button').at(0).simulate('click');

    expect(onChangeForm).toBeCalled();
});

test('Should not trigger onSubmit if password or user is missing', () => {
    const onSubmit = jest.fn();
    const loginForm = shallow(
        <LoginForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const event = {
        preventDefault: jest.fn(),
    };

    loginForm.find('Input[icon="su-user"]').prop('onChange')('Max');
    loginForm.find('form').prop('onSubmit')(event);

    expect(event.preventDefault).toBeCalledWith();
    expect(onSubmit).not.toBeCalled();
});

test('Should trigger onSubmit correctly', () => {
    const onSubmit = jest.fn();
    const loginForm = shallow(
        <LoginForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const event = {
        preventDefault: jest.fn(),
    };

    loginForm.find('Input[icon="su-user"]').prop('onChange')('Max');
    loginForm.find('Input[icon="su-lock"]').prop('onChange')('max');
    loginForm.find('form').prop('onSubmit')(event);

    expect(event.preventDefault).toBeCalledWith();
    expect(onSubmit).toBeCalledWith('Max', 'max');
});
