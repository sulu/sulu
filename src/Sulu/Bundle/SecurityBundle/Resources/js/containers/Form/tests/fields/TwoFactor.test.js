// @flow
import React from 'react';
import {act, render, screen, waitFor, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import {createDeferred} from 'sulu-admin-bundle/utils/TestHelper';
import {Requester} from 'sulu-admin-bundle/services';
import TwoFactor from '../../fields/TwoFactor';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/services', () => ({
    Requester: {
        post: jest.fn(),
    },
}));

jest.mock('react-qr-code', () => {
    const React = require('react');

    return jest.fn((props) => (
        React.createElement('div', {'data-testid': 'qr-code', 'data-value': props.value})
    ));
});

const formInspector: any = {
    getValueByPath: jest.fn(),
};

const schemaOptions = {
    values: {
        name: 'values',
        value: [
            {name: '', title: 'None'},
            {name: 'email', title: 'Email'},
            {name: 'totp', title: 'Totp'},
            {name: 'google', title: 'Google Authenticator'},
        ],
    },
};

beforeEach(() => {
    jest.clearAllMocks();
    formInspector.getValueByPath.mockReturnValue(false);
    TwoFactor.endpoints = {
        twoFactorSetup: '/setup',
        twoFactorConfirm: '/confirm',
        twoFactorBackupCodes: '/backup-codes',
    };
    TwoFactor.backupCodesEnabled = true;
});

function renderTwoFactor(props: Object = {}) {
    const defaultProps = {
        ...fieldTypeDefaultProps,
        formInspector,
        schemaOptions,
    };
    const view = render(<TwoFactor {...defaultProps} {...props} />);

    return {
        ...view,
        rerenderTwoFactor: (newProps: Object) => view.rerender(
            <TwoFactor {...defaultProps} {...newProps} />
        ),
    };
}

async function selectMethod(user, title: string) {
    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText(title));
}

function getDialog(title: string): HTMLElement {
    const dialog = screen.getAllByText(title)
        .map((element) => element.closest('.dialogContainer'))
        .find(Boolean);

    if (!(dialog instanceof HTMLElement)) {
        throw new Error('Expected dialog to be open');
    }

    return dialog;
}

function getOverlay(title: string): HTMLElement {
    const overlay = screen.getByText(title).closest('.container');

    if (!(overlay instanceof HTMLElement)) {
        throw new Error('Expected overlay to be open');
    }

    return overlay;
}

test('Render a SingleSelect with the given values and value', async() => {
    const user = userEvent.setup();

    renderTwoFactor({value: 'email'});

    expect(screen.getByText('Email')).toBeInTheDocument();
    expect(screen.queryByText('sulu_security.two_factor_setup_title')).not.toBeInTheDocument();

    await user.click(screen.getByLabelText('su-angle-down'));

    expect(screen.getByText('None')).toBeInTheDocument();
    expect(screen.getAllByText('Email')).not.toHaveLength(0);
    expect(screen.getByText('Totp')).toBeInTheDocument();
    expect(screen.getByText('Google Authenticator')).toBeInTheDocument();
});

test('Call onChange and onFinish directly for methods without setup', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    renderTwoFactor({onChange: changeSpy, onFinish: finishSpy});
    await selectMethod(user, 'Email');

    expect(changeSpy).toHaveBeenCalledWith('email');
    expect(finishSpy).toHaveBeenCalled();
    expect(Requester.post).not.toHaveBeenCalled();
});

test.each([
    ['totp', 'Totp'],
    ['google', 'Google Authenticator'],
])('Start the setup flow when the %s method is selected', async(method, title) => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    Requester.post.mockResolvedValue({secret: 'SECRET', qrContent: 'otpauth://totp/test'});

    renderTwoFactor({onChange: changeSpy});
    await selectMethod(user, title);

    expect(Requester.post).toHaveBeenCalledWith('/setup', {method});
    expect(changeSpy).not.toHaveBeenCalled();
    await waitFor(() => expect(screen.getByTestId('qr-code'))
        .toHaveAttribute('data-value', 'otpauth://totp/test'));
    expect(screen.getByText('SECRET')).toBeInTheDocument();
});

test('Ignore stale setup responses when another method was selected in the meantime', async() => {
    const user = userEvent.setup();
    const firstSetup = createDeferred<Object>();
    const secondSetup = createDeferred<Object>();
    Requester.post.mockReturnValueOnce(firstSetup.promise).mockReturnValueOnce(secondSetup.promise);

    renderTwoFactor();
    await selectMethod(user, 'Totp');
    await selectMethod(user, 'Google Authenticator');

    await act(async() => secondSetup.resolve({secret: 'GOOGLE', qrContent: 'otpauth://totp/google'}));
    await waitFor(() => expect(screen.getByTestId('qr-code'))
        .toHaveAttribute('data-value', 'otpauth://totp/google'));

    await act(async() => firstSetup.resolve({secret: 'TOTP', qrContent: 'otpauth://totp/totp'}));
    expect(screen.getByTestId('qr-code')).toHaveAttribute('data-value', 'otpauth://totp/google');
});

test('Do not open the overlay when a method without setup was selected in the meantime', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const setupRequest = createDeferred<Object>();
    Requester.post.mockReturnValue(setupRequest.promise);

    renderTwoFactor({onChange: changeSpy});
    await selectMethod(user, 'Totp');
    await selectMethod(user, 'Email');

    expect(changeSpy).toHaveBeenCalledWith('email');

    await act(async() => setupRequest.resolve({secret: 'SECRET', qrContent: 'otpauth://totp/test'}));
    expect(screen.queryByText('sulu_security.two_factor_setup_title')).not.toBeInTheDocument();
});

test('Activate the method and close the overlay when the code is confirmed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    Requester.post.mockResolvedValueOnce({secret: 'SECRET', qrContent: 'otpauth://totp/test'});

    renderTwoFactor({onChange: changeSpy, onFinish: finishSpy});
    await selectMethod(user, 'Totp');
    expect(await screen.findByText('SECRET')).toBeInTheDocument();

    await user.type(screen.getByRole('textbox'), '123456');
    Requester.post.mockResolvedValueOnce({});
    await user.click(screen.getByRole('button', {name: 'sulu_security.two_factor_setup_activate'}));

    expect(Requester.post).toHaveBeenLastCalledWith('/confirm', {method: 'totp', code: '123456'});
    await waitFor(() => expect(changeSpy).toHaveBeenCalledWith('totp'));
    expect(finishSpy).toHaveBeenCalled();
    expect(getOverlay('sulu_security.two_factor_setup_title')).not.toHaveClass('isDown');
});

test('Show the backup codes button only when a method is active and backup codes are enabled', () => {
    const {rerenderTwoFactor} = renderTwoFactor({value: 'email'});

    expect(screen.getByRole('button', {name: /sulu_security.two_factor_backup_codes_generate/}))
        .toBeInTheDocument();

    rerenderTwoFactor({value: undefined});
    expect(screen.queryByRole('button', {name: /sulu_security.two_factor_backup_codes_generate/}))
        .not.toBeInTheDocument();

    TwoFactor.backupCodesEnabled = false;
    rerenderTwoFactor({value: 'email'});
    expect(screen.queryByRole('button', {name: /sulu_security.two_factor_backup_codes_generate/}))
        .not.toBeInTheDocument();
});

test('Generate backup codes without a dialog when no backup codes exist yet', async() => {
    const user = userEvent.setup();
    const backupCodesRequest = createDeferred<Object>();
    Requester.post.mockReturnValue(backupCodesRequest.promise);

    renderTwoFactor({value: 'totp'});
    const generateButton = screen.getByRole('button', {
        name: /sulu_security.two_factor_backup_codes_generate/,
    });
    await user.click(generateButton);

    expect(screen.queryByText('sulu_security.two_factor_backup_codes_generate_warning')).not.toBeInTheDocument();
    expect(Requester.post).toHaveBeenCalledWith('/backup-codes');
    expect(generateButton).toBeDisabled();

    await act(async() => backupCodesRequest.resolve({backupCodes: ['11111111', '22222222']}));
    expect(await screen.findByText('11111111')).toBeInTheDocument();
    expect(screen.getByText('22222222')).toBeInTheDocument();

    await user.click(within(getOverlay('sulu_security.two_factor_backup_codes'))
        .getByRole('button', {name: 'sulu_admin.ok'}));
    await user.click(generateButton);

    expect(screen.getByText('sulu_security.two_factor_backup_codes_generate_warning')).toBeInTheDocument();
});

test('Generate backup codes after the dialog was confirmed when backup codes already exist', async() => {
    const user = userEvent.setup();
    formInspector.getValueByPath.mockReturnValue(true);

    renderTwoFactor({value: 'totp'});
    await user.click(screen.getByRole('button', {name: /sulu_security.two_factor_backup_codes_generate/}));

    expect(formInspector.getValueByPath).toHaveBeenCalledWith('/twoFactor/hasBackupCodes');
    expect(Requester.post).not.toHaveBeenCalled();

    const backupCodesRequest = createDeferred<Object>();
    Requester.post.mockReturnValue(backupCodesRequest.promise);
    await user.click(within(getDialog('sulu_security.two_factor_backup_codes_generate'))
        .getByRole('button', {name: 'sulu_admin.ok'}));

    expect(Requester.post).toHaveBeenCalledWith('/backup-codes');
    await act(async() => backupCodesRequest.resolve({backupCodes: ['11111111', '22222222']}));

    expect(await screen.findByText('11111111')).toBeInTheDocument();
    expect(screen.getByText('22222222')).toBeInTheDocument();
});

test('Do not generate backup codes when the dialog was cancelled', async() => {
    const user = userEvent.setup();
    formInspector.getValueByPath.mockReturnValue(true);

    renderTwoFactor({value: 'totp'});
    await user.click(screen.getByRole('button', {name: /sulu_security.two_factor_backup_codes_generate/}));

    const dialog = getDialog('sulu_security.two_factor_backup_codes_generate');
    await user.click(within(dialog).getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(dialog).not.toHaveClass('open');
    expect(Requester.post).not.toHaveBeenCalled();
});

test('Close the overlay without activating when the setup is aborted', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    Requester.post.mockResolvedValue({secret: 'SECRET', qrContent: 'otpauth://totp/test'});

    renderTwoFactor({onChange: changeSpy});
    await selectMethod(user, 'Totp');
    const overlay = getOverlay('sulu_security.two_factor_setup_title');

    await user.click(within(overlay).getByLabelText('su-times'));

    expect(overlay).not.toHaveClass('isDown');
    expect(changeSpy).not.toHaveBeenCalled();
});
