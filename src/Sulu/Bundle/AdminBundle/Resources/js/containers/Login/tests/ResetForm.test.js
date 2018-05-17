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
            user={undefined}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should render the component with data', () => {
    expect(render(
        <ResetForm
            user="test"
            password="test"
            onChangeForm={jest.fn()}
            onPasswordChange={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should render the component loading', () => {
    expect(render(
        <ResetForm
            error={true}
            user="test"
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should render the component with success', () => {
    expect(render(
        <ResetForm
            success={true}
            user="test"
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
        />)
    ).toMatchSnapshot();
});

test('Should trigger onUserChange correctly', () => {
    const onUserChange = jest.fn();
    const resetForm = shallow(
        <ResetForm
            user="test"
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
            onUserChange={onUserChange}
        />
    );

    resetForm.find('Input').at(0).simulate('change', 'test-user-123');

    expect(onUserChange).toBeCalledWith('test-user-123');
});

test('Should trigger onChangeForm correctly', () => {
    const onChangeForm = jest.fn();
    const resetForm = shallow(
        <ResetForm
            user="test"
            onChangeForm={onChangeForm}
            onSubmit={jest.fn()}
            onUserChange={jest.fn()}
        />
    );

    resetForm.find('Button').at(0).simulate('click');

    expect(onChangeForm).toBeCalled();
});

test('Should trigger onSubmit correctly', () => {
    const onSubmit = jest.fn();
    const resetForm = shallow(
        <ResetForm
            user="test"
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
            onUserChange={jest.fn()}
        />
    );
    resetForm.find('form').simulate('submit');

    expect(onSubmit).toBeCalled();
});
