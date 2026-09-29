// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import {initializer, Requester} from 'sulu-admin-bundle/services';
import {userStore} from 'sulu-admin-bundle/stores';
import TwoFactorSetupOverlay from '../TwoFactorSetupOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services', () => ({
    initializer: {
        initialize: jest.fn(),
    },
    Requester: {
        post: jest.fn(),
    },
}));

beforeEach(() => {
    TwoFactorSetupOverlay.endpoints = {
        twoFactorSetup: '/setup',
        twoFactorConfirm: '/confirm',
        twoFactorBackupCodes: '/backup-codes',
    };
    TwoFactorSetupOverlay.methods = ['totp', 'email'];
    TwoFactorSetupOverlay.backupCodesEnabled = true;
    userStore.setTwoFactorSetupRequired(true);
});

afterEach(() => {
    userStore.setTwoFactorSetupRequired(false);
});

test('Show the method selection when more than one method is configured', () => {
    render(<TwoFactorSetupOverlay />);

    expect(screen.getByText('sulu_security.two_factor_method_totp')).toBeInTheDocument();
    expect(screen.getByText('sulu_security.two_factor_method_email')).toBeInTheDocument();
    expect(Requester.post).not.toHaveBeenCalled();
});

test('Skip the method selection and start the setup right away when only one method is configured', async() => {
    TwoFactorSetupOverlay.methods = ['totp'];
    Requester.post.mockReturnValueOnce(Promise.resolve({secret: 'SECRET', qrContent: 'otpauth://totp/test'}));

    render(<TwoFactorSetupOverlay />);

    expect(Requester.post).toHaveBeenCalledWith('/setup', {method: 'totp'});
    expect(await screen.findByText('SECRET')).toBeInTheDocument();
    expect(screen.queryByText('sulu_security.two_factor_method_totp')).not.toBeInTheDocument();
});

test('Go through setup, the backup codes question, creating them and finishing', async() => {
    Requester.post.mockReturnValueOnce(Promise.resolve({secret: 'SECRET', qrContent: 'otpauth://totp/test'}));

    render(<TwoFactorSetupOverlay />);

    await userEvent.click(screen.getByText('sulu_security.two_factor_method_totp'));

    await userEvent.type(await screen.findByRole('textbox'), '123456');

    Requester.post.mockReturnValueOnce(Promise.resolve({}));
    await userEvent.click(screen.getByRole('button', {name: 'sulu_security.two_factor_setup_activate'}));

    expect(Requester.post).toHaveBeenCalledWith('/confirm', {method: 'totp', code: '123456'});

    // backup codes are enabled, so the wizard asks before creating them
    const createBackupCodesButton = await screen.findByRole(
        'button',
        {name: 'sulu_security.two_factor_setup_create_backup_codes'}
    );

    Requester.post.mockReturnValueOnce(Promise.resolve({backupCodes: ['11111111', '22222222']}));
    await userEvent.click(createBackupCodesButton);

    expect(Requester.post).toHaveBeenCalledWith('/backup-codes');
    expect(await screen.findByText('11111111')).toBeInTheDocument();
    expect(screen.getByText('22222222')).toBeInTheDocument();

    await userEvent.click(screen.getByRole('button', {name: 'sulu_security.two_factor_setup_finish'}));

    await waitFor(() => expect(initializer.initialize).toHaveBeenCalledWith(true));
});

test('Finish directly when the backup codes question is skipped', async() => {
    Requester.post.mockReturnValueOnce(Promise.resolve({secret: 'SECRET', qrContent: 'otpauth://totp/test'}));

    render(<TwoFactorSetupOverlay />);

    await userEvent.click(screen.getByText('sulu_security.two_factor_method_totp'));
    await userEvent.type(await screen.findByRole('textbox'), '123456');

    Requester.post.mockReturnValueOnce(Promise.resolve({}));
    await userEvent.click(screen.getByRole('button', {name: 'sulu_security.two_factor_setup_activate'}));

    const skipButton = await screen.findByRole(
        'button',
        {name: 'sulu_security.two_factor_setup_skip_backup_codes'}
    );
    await userEvent.click(skipButton);

    await waitFor(() => expect(initializer.initialize).toHaveBeenCalledWith(true));
});

test('Finish directly without asking for backup codes when they are disabled', async() => {
    TwoFactorSetupOverlay.backupCodesEnabled = false;
    Requester.post.mockReturnValueOnce(Promise.resolve({secret: 'SECRET', qrContent: 'otpauth://totp/test'}));

    render(<TwoFactorSetupOverlay />);

    await userEvent.click(screen.getByText('sulu_security.two_factor_method_totp'));
    await userEvent.type(await screen.findByRole('textbox'), '123456');

    Requester.post.mockReturnValueOnce(Promise.resolve({}));
    await userEvent.click(screen.getByRole('button', {name: 'sulu_security.two_factor_setup_activate'}));

    await waitFor(() => expect(initializer.initialize).toHaveBeenCalledWith(true));
});
