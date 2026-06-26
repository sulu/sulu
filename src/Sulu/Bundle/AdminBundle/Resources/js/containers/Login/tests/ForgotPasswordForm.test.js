// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ForgotPasswordForm from '../ForgotPasswordForm';

jest.mock('../../../utils/Translator');

test('Should render the component', () => {
    const {asFragment} = render(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the component loading', () => {
    const {asFragment} = render(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the component with success', () => {
    const {asFragment} = render(
        <ForgotPasswordForm
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
            success={true}
        />
    );

    expect(asFragment()).toMatchSnapshot();
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

    expect(onChangeForm).toHaveBeenCalled();
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

    expect(onSubmit).not.toHaveBeenCalled();
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

    await user.type(screen.getByLabelText('sulu_admin.username_or_email'), 'testusername');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.reset'}));

    expect(onSubmit).toHaveBeenCalledWith({user: 'testusername'});
});
