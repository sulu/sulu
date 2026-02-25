// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import GhostDialog from '../GhostDialog';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

let mockFormStoreData = {locale: 'en'};

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../Form', () => jest.fn(() => null));

jest.mock('../stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(() => ({
        data: mockFormStoreData,
    })),
}));

const formContainerComponent = ((jest.requireMock('../Form'): any): {
    mock: {calls: Array<[Object]>},
    ...
});

beforeEach(() => {
    jest.clearAllMocks();
    mockFormStoreData = {locale: 'en'};
});

test('Should render a Dialog', () => {
    const {baseElement} = render(
        <GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={jest.fn()} open={true} />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Should call onCancel callback if user chooses not to copy content', async() => {
    const user = userEvent.setup();
    const cancelSpy = jest.fn();
    render(
        <GhostDialog locales={['en', 'de']} onCancel={cancelSpy} onConfirm={jest.fn()} open={true} />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.no'}));

    expect(cancelSpy).toBeCalledWith();
});

test('Should call onConfirm callback with chosen locale if user chooses to copy content', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();
    render(
        <GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={confirmSpy} open={true} />
    );

    // Simulate selecting a different locale through the form container store.
    getLatestMockProps(formContainerComponent).store.data.locale = 'de';
    await user.click(screen.getByRole('button', {name: 'sulu_admin.yes'}));

    expect(confirmSpy).toBeCalledWith('de', {});
});

test('Should call onConfirm callback with chosen locale if user chooses to copy content (with additional fields)', async() => { // eslint-disable-line max-len
    const user = userEvent.setup();
    mockFormStoreData = {
        locale: 'de',
        title: 'Test 123',
    };
    const confirmSpy = jest.fn();

    render(
        <GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={confirmSpy} open={true} />
    );

    await user.click(screen.getByRole('button', {name: 'sulu_admin.yes'}));

    expect(confirmSpy).toBeCalledWith('de', {
        title: 'Test 123',
    });
});
