// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import CustomUrlsDomainSelect from '../../fields/CustomUrlsDomainSelect';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-page-bundle/stores', () => ({
    webspaceStore: {
        getWebspace: jest.fn(),
    },
}));

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: {
        options: {
            webspace: 'sulu_io',
        },
    },
    ...props,
});

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass correct props to MultiSelect', async() => {
    const user = userEvent.setup();
    const webspace = {
        customUrls: [
            {url: 'www.sulu.io/*'},
            {url: '*.sulu.io'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    render(
        <CustomUrlsDomainSelect
            {...createProps({
                disabled: true,
                value: 'www.sulu.io/*',
            })}
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');

    const triggerButton = screen.getByRole('button', {name: /www\.sulu\.io\/\*/});
    expect(triggerButton).toBeDisabled();

    await user.click(triggerButton);
    expect(screen.queryByRole('button', {name: '*.sulu.io'})).not.toBeInTheDocument();
});

test('Render all domain options', async() => {
    const user = userEvent.setup();
    const webspace = {
        customUrls: [
            {url: 'www.sulu.io/*'},
            {url: '*.sulu.io'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    render(
        <CustomUrlsDomainSelect
            {...createProps({
                value: 'www.sulu.io/*',
            })}
        />
    );

    await user.click(screen.getByRole('button', {name: /www\.sulu\.io\/\*/}));

    const optionsList = screen.getByRole('list');
    expect(within(optionsList).getByRole('button', {name: /www\.sulu\.io\/\*/})).toBeInTheDocument();
    expect(within(optionsList).getByRole('button', {name: '*.sulu.io'})).toBeInTheDocument();
});

test('Call onChange and onBlur if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const webspace = {
        customUrls: [
            {url: 'www.sulu.io/*'},
            {url: '*.sulu.io'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    render(
        <CustomUrlsDomainSelect
            {...createProps({
                onChange: changeSpy,
                onFinish: finishSpy,
                value: 'www.sulu.io/*',
            })}
        />
    );

    await user.click(screen.getByRole('button', {name: /www\.sulu\.io\/\*/}));
    await user.click(screen.getByRole('button', {name: '*.sulu.io'}));

    expect(changeSpy).toBeCalledWith('*.sulu.io');
    expect(finishSpy).toBeCalledWith();
});
