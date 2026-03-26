// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
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

function createFormInspector() {
    return ({
        options: {webspace: 'sulu_io'},
    }: any);
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass correct props to MultiSelect', () => {
    const webspace = {
        customUrls: [
            {url: 'www.sulu.io/*'},
            {url: '*.sulu.io'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    render(
        <CustomUrlsDomainSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector()}
            value="www.sulu.io/*"
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');
    expect(screen.getByRole('button', {name: /^www\.sulu\.io\/\*/})).toBeDisabled();
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
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="www.sulu.io/*"
        />
    );

    await user.click(screen.getByRole('button', {name: /^www\.sulu\.io\/\*/}));
    await user.click(screen.getByRole('button', {name: /^\*\.sulu\.io/}));

    expect(changeSpy).toBeCalledWith('*.sulu.io');
    expect(finishSpy).toBeCalledWith();
});
