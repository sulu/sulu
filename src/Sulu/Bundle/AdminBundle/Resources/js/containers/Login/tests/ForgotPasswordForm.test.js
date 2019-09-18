// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import ForgotPasswordForm from '../ForgotPasswordForm';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn(function(key) {
        return key;
    }),
}));

test('Should render the component', () => {
    expect(render(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should render the component loading', () => {
    expect(render(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should render the component with success', () => {
    expect(render(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
            success={true}
        />)
    ).toMatchSnapshot();
});

test('Should trigger onChangeForm correctly', () => {
    const onChangeForm = jest.fn();
    const resetForm = shallow(
        <ForgotPasswordForm
            onChangeForm={onChangeForm}
            onSubmit={jest.fn()}
        />
    );

    resetForm.find('Button').at(0).simulate('click');

    expect(onChangeForm).toBeCalled();
});

test('Should not trigger onSubmit if user is missing', () => {
    const onSubmit = jest.fn();
    const resetForm = shallow(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const event = {
        preventDefault: jest.fn(),
    };

    resetForm.find('form').prop('onSubmit')(event);

    expect(event.preventDefault).toBeCalledWith();
    expect(onSubmit).not.toBeCalled();
});

test('Should trigger onSubmit correctly', () => {
    const onSubmit = jest.fn();
    const resetForm = shallow(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const event = {
        preventDefault: jest.fn(),
    };

    resetForm.find('Input[icon="su-user"]').prop('onChange')('Max');
    resetForm.find('form').prop('onSubmit')(event);

    expect(event.preventDefault).toBeCalledWith();
    expect(onSubmit).toBeCalledWith('Max');
});
