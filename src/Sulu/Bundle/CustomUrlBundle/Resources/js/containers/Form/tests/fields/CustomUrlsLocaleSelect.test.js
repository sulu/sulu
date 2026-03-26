// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import CustomUrlsLocaleSelect from '../../fields/CustomUrlsLocaleSelect';

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
        allLocalizations: [
            {localization: 'de'},
            {localization: 'en'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    render(
        <CustomUrlsLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector()}
            value="en"
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');
    expect(screen.getByRole('button', {name: /^en/})).toBeDisabled();
});

test('Call onChange and onBlur if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const webspace = {
        allLocalizations: [
            {localization: 'de'},
            {localization: 'en'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    render(
        <CustomUrlsLocaleSelect
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="de"
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');

    await user.click(screen.getByRole('button', {name: /^de/}));
    await user.click(screen.getByRole('button', {name: /^en/}));

    expect(changeSpy).toBeCalledWith('en');
    expect(finishSpy).toBeCalledWith();
});
