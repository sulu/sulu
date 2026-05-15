// @flow
import React from 'react';
import userEvent from '@testing-library/user-event';
import {render, screen} from '@testing-library/react';
import ForgotPasswordForm from '../ForgotPasswordForm';

test('Should render the component', () => {
    render(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(screen.getByText('sulu_admin.forgot_password')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.reset'})).toBeDisabled();
});

test('Should render the component loading', () => {
    render(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(screen.getByText('sulu_admin.forgot_password')).toBeInTheDocument();
});

test('Should render the component with success', () => {
    render(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
            success={true}
        />
    );

    expect(screen.getByText('sulu_admin.forgot_password_success')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.reset_resend'})).toBeDisabled();
});

test('Should trigger onChangeForm correctly', async() => {
    const user = userEvent.setup();
    const onChangeForm = jest.fn();
    render(
        <ForgotPasswordForm
            onChangeForm={onChangeForm}
            onSubmit={jest.fn()}
        />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.back_to_login'}));

    expect(onChangeForm).toBeCalled();
});

test('Should not trigger onSubmit if user is missing', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset'}));
    expect(onSubmit).not.toBeCalled();
});

test('Should trigger onSubmit correctly', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    await user.type(screen.getByRole('textbox'), 'testusername');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset'}));

    expect(onSubmit).toBeCalledWith({user: 'testusername'});
});
