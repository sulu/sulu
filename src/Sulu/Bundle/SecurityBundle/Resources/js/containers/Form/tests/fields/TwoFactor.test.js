// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import QRCode from 'react-qr-code';
import {Button, Dialog, Input, Overlay, SingleSelect} from 'sulu-admin-bundle/components';
import {Requester} from 'sulu-admin-bundle/services';
import TwoFactor from '../../fields/TwoFactor';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services', () => ({
    Requester: {
        post: jest.fn(),
    },
}));

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
    TwoFactor.endpoints = {
        twoFactorSetup: '/setup',
        twoFactorConfirm: '/confirm',
        twoFactorBackupCodes: '/backup-codes',
    };
    TwoFactor.backupCodesEnabled = true;
});

test('Render a SingleSelect with the given values and value', () => {
    const twoFactor = shallow(
        <TwoFactor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="email"
        />
    );

    expect(twoFactor.find(SingleSelect).prop('value')).toEqual('email');
    expect(twoFactor.find(SingleSelect.Option)).toHaveLength(4);
    expect(twoFactor.find(Overlay).at(0).prop('open')).toEqual(false);
});

test('Call onChange and onFinish directly for methods without setup', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const twoFactor = shallow(
        <TwoFactor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    twoFactor.find(SingleSelect).prop('onChange')('email');

    expect(changeSpy).toHaveBeenCalledWith('email');
    expect(finishSpy).toHaveBeenCalled();
    expect(Requester.post).not.toHaveBeenCalled();
});

test('Start the setup flow when an authenticator app method is selected', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const setupPromise = Promise.resolve({secret: 'SECRET', qrContent: 'otpauth://totp/test'});
    Requester.post.mockReturnValue(setupPromise);

    const twoFactor = shallow(
        <TwoFactor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    twoFactor.find(SingleSelect).prop('onChange')('totp');

    expect(Requester.post).toHaveBeenCalledWith('/setup', {method: 'totp'});
    expect(changeSpy).not.toHaveBeenCalled();

    return setupPromise.then(() => {
        twoFactor.update();
        expect(twoFactor.find(Overlay).at(0).prop('open')).toEqual(true);
        expect(twoFactor.find(QRCode).prop('value')).toEqual('otpauth://totp/test');
    });
});

test('Start the setup flow when the google authenticator method is selected', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const setupPromise = Promise.resolve({secret: 'SECRET', qrContent: 'otpauth://totp/test'});
    Requester.post.mockReturnValue(setupPromise);

    const twoFactor = shallow(
        <TwoFactor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    twoFactor.find(SingleSelect).prop('onChange')('google');

    expect(Requester.post).toHaveBeenCalledWith('/setup', {method: 'google'});
    expect(changeSpy).not.toHaveBeenCalled();

    return setupPromise.then(() => {
        twoFactor.update();
        expect(twoFactor.find(Overlay).at(0).prop('open')).toEqual(true);
    });
});

test('Activate the method and close the overlay when the code is confirmed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const setupPromise = Promise.resolve({secret: 'SECRET', qrContent: 'otpauth://totp/test'});
    Requester.post.mockReturnValue(setupPromise);

    const twoFactor = shallow(
        <TwoFactor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    twoFactor.find(SingleSelect).prop('onChange')('totp');

    return setupPromise.then(() => {
        twoFactor.update();

        twoFactor.find(Input).prop('onChange')('123456');
        twoFactor.update();

        const confirmPromise = Promise.resolve({});
        Requester.post.mockReturnValue(confirmPromise);

        twoFactor.find(Overlay).at(0).prop('onConfirm')();

        expect(Requester.post).toHaveBeenCalledWith('/confirm', {method: 'totp', code: '123456'});

        return confirmPromise.then(() => {
            twoFactor.update();

            expect(changeSpy).toHaveBeenCalledWith('totp');
            expect(finishSpy).toHaveBeenCalled();
            expect(twoFactor.find(Overlay).at(0).prop('open')).toEqual(false);
        });
    });
});

test('Show the backup codes button only when a method is active and backup codes are enabled', () => {
    const twoFactor = shallow(
        <TwoFactor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="email"
        />
    );

    expect(twoFactor.find(Button).filterWhere((button) => button.prop('icon') === 'su-sync')).toHaveLength(1);

    twoFactor.setProps({value: undefined});
    expect(twoFactor.find(Button).filterWhere((button) => button.prop('icon') === 'su-sync')).toHaveLength(0);

    TwoFactor.backupCodesEnabled = false;
    twoFactor.setProps({value: 'email'});
    expect(twoFactor.find(Button).filterWhere((button) => button.prop('icon') === 'su-sync')).toHaveLength(0);
});

test('Generate backup codes without a dialog when no backup codes exist yet', () => {
    formInspector.getValueByPath.mockReturnValue(false);

    const backupCodesPromise = Promise.resolve({backupCodes: ['11111111', '22222222']});
    Requester.post.mockReturnValue(backupCodesPromise);

    const twoFactor = shallow(
        <TwoFactor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="totp"
        />
    );

    twoFactor.find(Button).filterWhere((button) => button.prop('icon') === 'su-sync').prop('onClick')();
    twoFactor.update();

    expect(twoFactor.find(Dialog).prop('open')).toEqual(false);
    expect(Requester.post).toHaveBeenCalledWith('/backup-codes');
    expect(
        twoFactor.find(Button).filterWhere((button) => button.prop('icon') === 'su-sync').prop('loading')
    ).toEqual(true);

    return backupCodesPromise.then(() => {
        twoFactor.update();

        expect(twoFactor.find(Overlay).at(1).prop('open')).toEqual(true);
        expect(
            twoFactor.find(Button).filterWhere((button) => button.prop('icon') === 'su-sync').prop('loading')
        ).toEqual(false);

        // a second generation would invalidate the codes that were just generated
        twoFactor.find(Button).filterWhere((button) => button.prop('icon') === 'su-sync').prop('onClick')();
        twoFactor.update();

        expect(twoFactor.find(Dialog).prop('open')).toEqual(true);
    });
});

test('Generate backup codes after the dialog was confirmed when backup codes already exist', () => {
    formInspector.getValueByPath.mockReturnValue(true);

    const twoFactor = shallow(
        <TwoFactor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="totp"
        />
    );

    twoFactor.find(Button).filterWhere((button) => button.prop('icon') === 'su-sync').prop('onClick')();
    twoFactor.update();

    expect(formInspector.getValueByPath).toHaveBeenCalledWith('/twoFactor/hasBackupCodes');
    expect(twoFactor.find(Dialog).prop('open')).toEqual(true);
    expect(Requester.post).not.toHaveBeenCalled();

    const backupCodesPromise = Promise.resolve({backupCodes: ['11111111', '22222222']});
    Requester.post.mockReturnValue(backupCodesPromise);

    twoFactor.find(Dialog).prop('onConfirm')();

    expect(Requester.post).toHaveBeenCalledWith('/backup-codes');

    return backupCodesPromise.then(() => {
        twoFactor.update();

        expect(twoFactor.find(Dialog).prop('open')).toEqual(false);
        expect(twoFactor.find(Overlay).at(1).prop('open')).toEqual(true);
        expect(twoFactor.find('li').map((element) => element.text())).toEqual(['11111111', '22222222']);
    });
});

test('Do not generate backup codes when the dialog was cancelled', () => {
    formInspector.getValueByPath.mockReturnValue(true);

    const twoFactor = shallow(
        <TwoFactor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="totp"
        />
    );

    twoFactor.find(Button).filterWhere((button) => button.prop('icon') === 'su-sync').prop('onClick')();
    twoFactor.update();

    twoFactor.find(Dialog).prop('onCancel')();
    twoFactor.update();

    expect(twoFactor.find(Dialog).prop('open')).toEqual(false);
    expect(Requester.post).not.toHaveBeenCalled();
});

test('Close the overlay without activating when the setup is aborted', () => {
    const changeSpy = jest.fn();

    const setupPromise = Promise.resolve({secret: 'SECRET', qrContent: 'otpauth://totp/test'});
    Requester.post.mockReturnValue(setupPromise);

    const twoFactor = shallow(
        <TwoFactor
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    twoFactor.find(SingleSelect).prop('onChange')('totp');

    return setupPromise.then(() => {
        twoFactor.update();
        expect(twoFactor.find(Overlay).at(0).prop('open')).toEqual(true);

        twoFactor.find(Overlay).at(0).prop('onClose')();
        twoFactor.update();

        expect(twoFactor.find(Overlay).at(0).prop('open')).toEqual(false);
        expect(changeSpy).not.toHaveBeenCalled();
    });
});
