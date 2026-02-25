// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import LoginForm from '../LoginForm';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should render the component', () => {
    const {asFragment} = render(<LoginForm onChangeForm={jest.fn()} onSubmit={jest.fn()} />);

    expect(screen.getByText('sulu_admin.welcome')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Should disable login button while loading', async() => {
    const user = userEvent.setup();
    render(<LoginForm loading={true} mode="username_only" onChangeForm={jest.fn()} onSubmit={jest.fn()} />);

    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'Max');

    expect(screen.getByRole('button', {name: 'sulu_admin.login'})).toBeDisabled();
});

test('Should render the component with error', () => {
    render(<LoginForm error={true} onChangeForm={jest.fn()} onSubmit={jest.fn()} />);

    expect(screen.getByText('sulu_admin.login_error')).toBeInTheDocument();
});

test('Should trigger onChangeForm correctly', async() => {
    const user = userEvent.setup();
    const onChangeForm = jest.fn();
    render(<LoginForm onChangeForm={onChangeForm} onSubmit={jest.fn()} />);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.forgot_password'}));

    expect(onChangeForm).toBeCalled();
});

test('Should not trigger onSubmit if password or user is missing', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(<LoginForm onChangeForm={jest.fn()} onSubmit={onSubmit} />);

    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'Max');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.login'}));

    expect(onSubmit).not.toBeCalled();
});

test('Should trigger onSubmit correctly', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(<LoginForm onChangeForm={jest.fn()} onSubmit={onSubmit} />);

    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'Max');
    await user.type(screen.getByLabelText('sulu_admin.password'), 'max');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.login'}));

    expect(onSubmit).toBeCalledWith({password: 'max', username: 'Max'});
});
