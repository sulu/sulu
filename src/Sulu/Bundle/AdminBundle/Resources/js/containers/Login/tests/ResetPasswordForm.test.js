// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import ResetPasswordForm from '../ResetPasswordForm';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn(function(key) {
        return key;
    }),
}));

test('Should render the component', () => {
    expect(render(
        <ResetPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should render the component loading', () => {
    expect(render(
        <ResetPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should trigger onChangeForm correctly', () => {
    const onChangeForm = jest.fn();
    const resetForm = shallow(
        <ResetPasswordForm
            onChangeForm={onChangeForm}
            onSubmit={jest.fn()}
        />
    );

    resetForm.find('Button').at(0).simulate('click');

    expect(onChangeForm).toBeCalled();
});

test('Should not trigger onSubmit if passwords are missing', () => {
    const onSubmit = jest.fn();
    const resetForm = shallow(
        <ResetPasswordForm
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
        <ResetPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const event = {
        preventDefault: jest.fn(),
    };

    resetForm.find('Input[icon="su-lock"]').at(0).prop('onChange')('max');
    resetForm.find('Input[icon="su-lock"]').at(1).prop('onChange')('max');
    resetForm.find('form').prop('onSubmit')(event);

    expect(event.preventDefault).toBeCalledWith();
    expect(onSubmit).toBeCalledWith('max');
});

test('Should not trigger onSubmit if one password is missing', () => {
    const onSubmit = jest.fn();
    const resetForm = shallow(
        <ResetPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const event = {
        preventDefault: jest.fn(),
    };

    resetForm.find('Input[icon="su-lock"]').at(0).prop('onChange')('max');
    resetForm.find('form').prop('onSubmit')(event);

    expect(event.preventDefault).toBeCalledWith();

    resetForm.update();
    expect(resetForm.find('Input[valid=false]')).toHaveLength(2);
});
