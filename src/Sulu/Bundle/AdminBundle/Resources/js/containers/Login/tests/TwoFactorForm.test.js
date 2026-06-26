// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import TwoFactorForm from '../TwoFactorForm';

jest.mock('../../../utils/Translator');

test('Should render the component', () => {
    const {asFragment} = render(
        <TwoFactorForm
            methods={['emails', 'trusted_devices']}
            onChangeForm={jest.fn()}
            onSubmit={jest.fn()}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the component error', () => {
    const {asFragment} = render(
        <TwoFactorForm
            error={true}
            methods={['emails', 'trusted_devices']}
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
        <TwoFactorForm
            methods={['emails', 'trusted_devices']}
            onChangeForm={onChangeForm}
            onSubmit={jest.fn()}
        />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.back_to_login'}));

    expect(onChangeForm).toHaveBeenCalled();
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

    expect(onSubmit).not.toHaveBeenCalled();
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

    expect(onSubmit).toHaveBeenCalledWith({_auth_code: 'authcode', _trusted: false});
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
    await user.click(screen.getByLabelText('sulu_admin.two_factor_trust_device'));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.verify'}));

    expect(onSubmit).toHaveBeenCalledWith({_auth_code: 'authcode', _trusted: true});
});
