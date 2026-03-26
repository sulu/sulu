// @flow
import React from 'react';
import userEvent from '@testing-library/user-event';
import {fireEvent, render, screen} from '@testing-library/react';
import ResetPasswordForm from '../ResetPasswordForm';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn(function(key) {
        return key;
    }),
}));

test('Should render the component', () => {
    render(
        <ResetPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(screen.getByText('sulu_admin.repeat_password')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.reset_password'})).toBeDisabled();
});

test('Should render the component loading', () => {
    render(
        <ResetPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(screen.getByText('sulu_admin.repeat_password')).toBeInTheDocument();
});

test('Should trigger onChangeForm correctly', async() => {
    const user = userEvent.setup();
    const onChangeForm = jest.fn();
    render(
        <ResetPasswordForm
            onChangeForm={onChangeForm}
            onSubmit={jest.fn()}
        />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.back_to_login'}));

    expect(onChangeForm).toBeCalled();
});

test('Should not trigger onSubmit if passwords are missing', async() => {
    const onSubmit = jest.fn();
    render(
        <ResetPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const form = screen.getByRole('button', {name: 'sulu_admin.reset_password'}).closest('form');
    if (!form) {
        throw new Error('Expected reset password form');
    }
    fireEvent.submit(form);

    expect(onSubmit).not.toBeCalled();
});

test('Should trigger onSubmit correctly', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(
        <ResetPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const passwordInputs = screen.getAllByLabelText('sulu_admin.password');
    await user.type(passwordInputs[0], 'testpassword');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'testpassword');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(onSubmit).toBeCalledWith({password: 'testpassword'});
});

test('Should not trigger onSubmit if one password is missing', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(
        <ResetPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const passwordInputs = screen.getAllByLabelText('sulu_admin.password');
    await user.type(passwordInputs[0], 'testpassword');
    const form = screen.getByRole('button', {name: 'sulu_admin.reset_password'}).closest('form');
    if (!form) {
        throw new Error('Expected reset password form');
    }
    fireEvent.submit(form);

    expect(onSubmit).not.toBeCalled();
    expect(screen.getByText('sulu_admin.reset_password_error')).toBeInTheDocument();
});
