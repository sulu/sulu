// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import AnalyticsDomainSelect from '../../fields/AnalyticsDomainSelect';

beforeEach(() => {
    webspaceStore.setWebspaces([]);
});

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

test('Pass correct props to MultiSelect', () => {
    const webspace = {
        urls: [
            {url: '{host}/{localization}'},
            {url: '{host}'},
        ],
    };
    setCurrentWebspace(webspace);

    render(
        <AnalyticsDomainSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector()}
            value={['{host}']}
        />
    );

    expect(screen.getByRole('button', {name: /^\{host\}/})).toBeDisabled();
});

test('Call onChange and onBlur if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const webspace = {
        urls: [
            {url: '{host}/{localization}'},
            {url: '{host}'},
        ],
    };
    setCurrentWebspace(webspace);

    render(
        <AnalyticsDomainSelect
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={['{host}']}
        />
    );

    await user.click(screen.getByRole('button', {name: /^\{host\}/}));
    await user.click(screen.getByRole('button', {name: /^\{host\}\/\{localization\}/}));

    expect(changeSpy).toBeCalledWith(['{host}', '{host}/{localization}']);
    expect(finishSpy).toBeCalledWith();
});
