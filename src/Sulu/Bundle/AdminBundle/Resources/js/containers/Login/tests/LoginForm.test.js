// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import LoginForm from '../LoginForm';

jest.mock('../../../utils/Translator');

test('Should render the component', () => {
    const {asFragment} = render(
        <LoginForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the component loading', () => {
    const {asFragment} = render(
        <LoginForm
            loading={true}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the component with error', () => {
    const {asFragment} = render(
        <LoginForm
            error={true}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(asFragment()).toMatchSnapshot();
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

    expect(onChangeForm).toHaveBeenCalled();
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

    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'Max');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.login'}));

    expect(onSubmit).not.toHaveBeenCalled();
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

    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'Max');
    await user.type(screen.getByLabelText('sulu_admin.password'), 'max');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.login'}));

    expect(onSubmit).toHaveBeenCalledWith({username: 'Max', password: 'max'});
});
