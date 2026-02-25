// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import AnalyticsDomainSelect from '../../fields/AnalyticsDomainSelect';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-page-bundle/stores', () => ({
    webspaceStore: {
        getWebspace: jest.fn(),
    },
}));

test('Pass correct props to MultiSelect', () => {
    const webspace = {
        urls: [
            {url: '{host}/{localization}'},
            {url: '{host}'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    const formInspector: any = {
        options: {
            webspace: 'sulu_io',
        },
    };

    render(
        <AnalyticsDomainSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={['{host}']}
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');
    expect(screen.getByRole('button', {name: /\{host\}/})).toBeDisabled();
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
    webspaceStore.getWebspace.mockReturnValue(webspace);

    const formInspector: any = {
        options: {
            webspace: 'sulu_io',
        },
    };

    render(
        <AnalyticsDomainSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={['{host}']}
        />
    );

    await user.click(screen.getByRole('button', {name: /\{host\}/}));
    await user.click(screen.getByRole('button', {name: '{host}/{localization}'}));

    expect(changeSpy).toBeCalledWith(['{host}', '{host}/{localization}']);
    expect(finishSpy).toBeCalledWith();
});
