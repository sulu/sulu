// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import CustomUrlsLocaleSelect from '../../fields/CustomUrlsLocaleSelect';

function createFormInspector() {
    return ({
        options: {webspace: 'sulu_io'},
    }: any);
}

function setCurrentWebspace(webspace) {
    webspaceStore.setWebspaces([({
        key: 'sulu_io',
        ...webspace,
    }: any)]);
}

beforeEach(() => {
    webspaceStore.setWebspaces([]);
});

test('Pass correct props to MultiSelect', () => {
    const webspace = {
        allLocalizations: [
            {localization: 'de'},
            {localization: 'en'},
        ],
    };
    setCurrentWebspace(webspace);

    render(
        <CustomUrlsLocaleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector()}
            value="en"
        />
    );

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
    setCurrentWebspace(webspace);

    render(
        <CustomUrlsLocaleSelect
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="de"
        />
    );

    await user.click(screen.getByRole('button', {name: /^de/}));
    await user.click(screen.getByRole('button', {name: /^en/}));

    expect(changeSpy).toBeCalledWith('en');
    expect(finishSpy).toBeCalledWith();
});
