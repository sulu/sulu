// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResetPasswordForm from '../ResetPasswordForm';

jest.mock('../../../utils/Translator');

test('Should render the component', () => {
    const {asFragment} = render(
        <ResetPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the component loading', () => {
    const {asFragment} = render(
        <ResetPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

function submitForm() {
    const button = screen.getByRole('button', {name: 'sulu_admin.reset_password'});
    const form = button.closest('form');

    if (!form) {
        throw new Error('Expected reset password form');
    }

    fireEvent.submit(form);
}

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

    expect(onChangeForm).toHaveBeenCalled();
});

test('Should not trigger onSubmit if passwords are missing', () => {
    const onSubmit = jest.fn();
    render(
        <ResetPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    submitForm();

    expect(onSubmit).not.toHaveBeenCalled();
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

    await user.type(screen.getByLabelText('sulu_admin.password'), 'testpassword');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'testpassword');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(onSubmit).toHaveBeenCalledWith({password: 'testpassword'});
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

    await user.type(screen.getByLabelText('sulu_admin.password'), 'testpassword');
    submitForm();

    expect(screen.getByText('sulu_admin.reset_password_error')).toBeInTheDocument();
    expect(onSubmit).not.toHaveBeenCalled();
});
