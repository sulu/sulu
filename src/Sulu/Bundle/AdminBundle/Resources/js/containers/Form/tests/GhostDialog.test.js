// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Dialog from '../../../components/Dialog';
import FormContainer from '../Form';
import GhostDialog from '../GhostDialog';
import memoryFormStoreFactory from '../stores/memoryFormStoreFactory';

jest.mock('../../../components/Dialog', () => jest.fn(({children}) => <div>{children}</div>));
jest.mock('../Form', () => jest.fn(() => null));
jest.mock('../stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(),
}));

function getLatestDialogProps() {
    const calls = (Dialog: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getLatestFormProps() {
    const calls = (FormContainer: any).mock.calls;
    return calls[calls.length - 1][0];
}

function createFormStore(data: Object = {locale: 'en'}) {
    return {data};
}

beforeEach(() => {
    jest.clearAllMocks();
    memoryFormStoreFactory.createFromFormKey.mockImplementation(() => createFormStore());
});

test('Should render a Dialog', () => {
    render(
        <GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={jest.fn()} open={true} />
    );

    expect(memoryFormStoreFactory.createFromFormKey).toBeCalledWith(
        'ghost_copy_locale',
        undefined,
        undefined,
        undefined,
        {locales: ['en', 'de']}
    );
    expect(getLatestDialogProps()).toEqual(expect.objectContaining({
        cancelText: 'sulu_admin.no',
        confirmText: 'sulu_admin.yes',
        open: true,
        title: 'sulu_admin.ghost_dialog_title',
    }));
});

test('Should call onCancel callback if user chooses not to copy content', () => {
    const cancelSpy = jest.fn();

    render(
        <GhostDialog locales={['en', 'de']} onCancel={cancelSpy} onConfirm={jest.fn()} open={true} />
    );

    getLatestDialogProps().onCancel();

    expect(cancelSpy).toBeCalledWith();
});

test('Should call onConfirm callback with chosen locale if user chooses to copy content', () => {
    const confirmSpy = jest.fn();

    render(
        <GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={confirmSpy} open={true} />
    );

    const formStore = getLatestFormProps().store;
    formStore.data = {locale: 'de'};
    getLatestDialogProps().onConfirm();

    expect(confirmSpy).toBeCalledWith('de', {});
});

test(
    'Should call onConfirm callback with chosen locale if user chooses to copy content (with additional fields)',
    () => {
        const confirmSpy = jest.fn();

        render(
            <GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={confirmSpy} open={true} />
        );

        const formStore = getLatestFormProps().store;
        formStore.data = {
            locale: 'de',
            title: 'Test 123',
        };
        getLatestDialogProps().onConfirm();

        expect(confirmSpy).toBeCalledWith('de', {
            title: 'Test 123',
        });
    }
);
