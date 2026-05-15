// @flow
import React from 'react';
import userEvent from '@testing-library/user-event';
import {render, screen} from '@testing-library/react';
import TwoFactorForm from '../TwoFactorForm';

test('Should render the component', () => {
    render(
        <TwoFactorForm
            methods={['emails', 'trusted_devices']}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(screen.getByText('sulu_admin.two_factor_authentication')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.verify'})).toBeDisabled();
});

test('Should render the component error', () => {
    render(
        <TwoFactorForm
            error={true}
            methods={['emails', 'trusted_devices']}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(screen.getByText('sulu_admin.two_factor_authentication_failed')).toBeInTheDocument();
});

test('Should trigger onChangeForm correctly', async() => {
    const user = userEvent.setup();
    const onChangeForm = jest.fn();
    render(
        <TwoFactorForm
            methods={['emails', 'trusted_devices']}
            onChangeForm={onChangeForm}
            onSubmit={jest.fn()}
        />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.back_to_login'}));

    expect(onChangeForm).toBeCalled();
});

test('Should not trigger onSubmit if autCode is missing', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(
        <TwoFactorForm
            methods={['emails', 'trusted_devices']}
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.verify'}));

    expect(onSubmit).not.toBeCalled();
});

test('Should trigger onSubmit correctly', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(
        <TwoFactorForm
            methods={['emails', 'trusted_devices']}
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    await user.type(screen.getByLabelText('sulu_admin.two_factor_verification_code'), 'authcode');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.verify'}));

    expect(onSubmit).toBeCalledWith({_auth_code: 'authcode', _trusted: false});
});

test('Should trigger onSubmit correctly with trusted device', async() => {
    const user = userEvent.setup();
    const onSubmit = jest.fn();
    render(
        <TwoFactorForm
            methods={['emails', 'trusted_devices']}
            onChangeForm={jest.fn()}
            onSubmit={onSubmit}
        />
    );

    await user.type(screen.getByLabelText('sulu_admin.two_factor_verification_code'), 'authcode');
    await user.click(screen.getByRole('checkbox', {name: 'sulu_admin.two_factor_trust_device'}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.verify'}));

    expect(onSubmit).toBeCalledWith({_auth_code: 'authcode', _trusted: true});
});
