// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResetPasswordForm from '../ResetPasswordForm';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores', () => ({
    userStore: {
        validatePassword: jest.fn(() => true),
    },
}));

test('Should render the component', () => {
    render(<ResetPasswordForm onChangeForm={jest.fn()} onSubmit={jest.fn()} />);

    expect(screen.getAllByText('sulu_admin.reset_password')).toHaveLength(2);
    expect(screen.getByRole('button', {name: 'sulu_admin.reset_password'})).toBeDisabled();
});

test('Should render the component loading', async() => {
    const user = userEvent.setup();
    render(<ResetPasswordForm loading={true} onChangeForm={jest.fn()} onSubmit={jest.fn()} />);

    await user.type(screen.getByLabelText('sulu_admin.password'), 'testpassword');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'testpassword');
    expect(screen.getByRole('button', {name: 'sulu_admin.reset_password'})).toBeDisabled();
    expect(document.querySelector('.spinner')).toBeInTheDocument();
});

test('Should trigger onChangeForm correctly', async() => {
    const user = userEvent.setup();
    const onChangeForm = jest.fn();
    render(<ResetPasswordForm onChangeForm={onChangeForm} onSubmit={jest.fn()} />);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.back_to_login'}));

    expect(onChangeForm).toBeCalled();
});

test('Should not trigger onSubmit if passwords are missing', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(<ResetPasswordForm onChangeForm={jest.fn()} onSubmit={onSubmit} />);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(onSubmit).not.toBeCalled();
});

test('Should trigger onSubmit correctly', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(<ResetPasswordForm onChangeForm={jest.fn()} onSubmit={onSubmit} />);

    await user.type(screen.getByLabelText('sulu_admin.password'), 'testpassword');
    await user.type(screen.getByLabelText('sulu_admin.repeat_password'), 'testpassword');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(onSubmit).toBeCalledWith({password: 'testpassword'});
});

test('Should not trigger onSubmit if one password is missing', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(<ResetPasswordForm onChangeForm={jest.fn()} onSubmit={onSubmit} />);

    await user.type(screen.getByLabelText('sulu_admin.password'), 'testpassword');
    expect(screen.getByRole('button', {name: 'sulu_admin.reset_password'})).toBeDisabled();
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset_password'}));

    expect(onSubmit).not.toBeCalled();
});
