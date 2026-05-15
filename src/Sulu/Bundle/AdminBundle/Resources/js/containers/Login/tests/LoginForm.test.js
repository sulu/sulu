// @flow
import React from 'react';
import userEvent from '@testing-library/user-event';
import {render, screen} from '@testing-library/react';
import LoginForm from '../LoginForm';

test('Should render the component', () => {
    render(
        <LoginForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(screen.getByText('sulu_admin.welcome')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.login'})).toBeDisabled();
});

test('Should render the component loading', () => {
    render(
        <LoginForm
            loading={true}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(screen.getByText('sulu_admin.welcome')).toBeInTheDocument();
});

test('Should render the component with error', () => {
    render(
        <LoginForm
            error={true}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(screen.getByText('sulu_admin.login_error')).toBeInTheDocument();
});

test('Should trigger onChangeForm correctly', async() => {
    const user = userEvent.setup();
    const onChangeForm = jest.fn();
    render(
        <LoginForm
            onChangeForm={onChangeForm}
            onSubmit={jest.fn()}
        />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.forgot_password'}));

    expect(onChangeForm).toBeCalled();
});

test('Should not trigger onSubmit if password or user is missing', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(
        <LoginForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const [usernameInput] = screen.getAllByRole('textbox');
    await user.type(usernameInput, 'Max');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.login'}));

    expect(onSubmit).not.toBeCalled();
});

test('Should trigger onSubmit correctly', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(
        <LoginForm
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    const [usernameInput] = screen.getAllByRole('textbox');
    const passwordInput = screen.getByLabelText('sulu_admin.password');
    await user.type(usernameInput, 'Max');
    await user.type(passwordInput, 'max');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.login'}));

    expect(onSubmit).toBeCalledWith({username: 'Max', password: 'max'});
});
