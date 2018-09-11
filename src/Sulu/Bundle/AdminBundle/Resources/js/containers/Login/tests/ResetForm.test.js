// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import ResetForm from '../ResetForm';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn(function(key) {
        return key;
    }),
}));

test('Should render the component', () => {
    expect(render(
        <ResetForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
            user={undefined}
        />)
    ).toMatchSnapshot();
});

test('Should render the component with data', () => {
    expect(render(
        <ResetForm
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
        <ResetForm
            error={true}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
            user="test"
        />)
    ).toMatchSnapshot();
});

test('Should render the component with success', () => {
    expect(render(
        <ResetForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
            success={true}
            user="test"
        />)
    ).toMatchSnapshot();
});

test('Should trigger onUserChange correctly', () => {
    const onUserChange = jest.fn();
    const resetForm = shallow(
        <ResetForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={onUserChange}
            user="test"
        />
    );

    resetForm.find('Input').at(0).simulate('change', 'test-user-123');

    expect(onUserChange).toBeCalledWith('test-user-123');
});

test('Should trigger onChangeForm correctly', () => {
    const onChangeForm = jest.fn();
    const resetForm = shallow(
        <ResetForm
            onChangeForm={onChangeForm}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
            user="test"
        />
    );

    resetForm.find('Button').at(0).simulate('click');

    expect(onChangeForm).toBeCalled();
});

test('Should trigger onSubmit correctly', () => {
    const onSubmit = jest.fn();
    const resetForm = shallow(
        <ResetForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
            onUserChange={jest.fn()}
            user="test"
        />
    );
    resetForm.find('form').simulate('submit');

    expect(onSubmit).toBeCalled();
});
