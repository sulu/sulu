// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ForgotPasswordForm from '../ForgotPasswordForm';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should render the component', () => {
    render(<ForgotPasswordForm onChangeForm={jest.fn()} onSubmit={jest.fn()} />);

    expect(screen.getByText('sulu_admin.forgot_password')).toBeInTheDocument();
});

test('Should render the component loading', async() => {
    const user = userEvent.setup();
    render(<ForgotPasswordForm loading={true} onChangeForm={jest.fn()} onSubmit={jest.fn()} />);

    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'testuser');
    expect(screen.getByRole('button', {name: 'sulu_admin.reset'})).toBeDisabled();
    expect(document.querySelector('.spinner')).toBeInTheDocument();
});

test('Should render the component with success', () => {
    render(<ForgotPasswordForm onChangeForm={jest.fn()} onSubmit={jest.fn()} success={true} />);

    expect(screen.getByText('sulu_admin.forgot_password_success')).toBeInTheDocument();
});

test('Should trigger onChangeForm correctly', async() => {
    const user = userEvent.setup();
    const onChangeForm = jest.fn();
    render(<ForgotPasswordForm onChangeForm={onChangeForm} onSubmit={jest.fn()} />);

    await user.click(screen.getByText('sulu_admin.back_to_login'));

    expect(onChangeForm).toBeCalled();
});

test('Should not trigger onSubmit if user is missing', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(<ForgotPasswordForm onChangeForm={jest.fn()} onSubmit={onSubmit} />);

    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), '{enter}');

    expect(onSubmit).not.toBeCalled();
});

test('Should trigger onSubmit correctly', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(<ForgotPasswordForm onChangeForm={jest.fn()} onSubmit={onSubmit} />);

    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'testusername{enter}');

    expect(onSubmit).toBeCalledWith({user: 'testusername'});
});
