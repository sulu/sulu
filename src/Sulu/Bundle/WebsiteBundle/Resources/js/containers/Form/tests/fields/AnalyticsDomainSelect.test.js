// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import AnalyticsDomainSelect from '../../fields/AnalyticsDomainSelect';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-page-bundle/stores', () => ({
    webspaceStore: {
        getWebspace: jest.fn(),
    },
}));

function createFormInspector(): any {
    return {
        options: {webspace: 'sulu_io'},
    };
}

beforeEach(() => {
    const webspace = {
        urls: [
            {url: '{host}/{localization}'},
            {url: '{host}'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);
});

test('Pass correct props to MultiSelect', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();

    const {unmount} = render(
        <AnalyticsDomainSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={['{host}']}
        />
    );

    expect(webspaceStore.getWebspace).toHaveBeenCalledWith('sulu_io');
    expect(screen.getByRole('button', {name: /\{host\}/})).toBeDisabled();

    unmount();

    render(
        <AnalyticsDomainSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={['{host}']}
        />
    );

    await user.click(screen.getByRole('button', {name: /\{host\}/}));

    expect(screen.getByRole('button', {name: /{host}\/{localization}$/})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /{host}$/})).toBeInTheDocument();
});

test('Call onChange and onBlur if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = createFormInspector();

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
    await user.click(screen.getByRole('button', {name: /{host}\/{localization}$/}));

    expect(changeSpy).toHaveBeenCalledWith(['{host}', '{host}/{localization}']);
    expect(finishSpy).toHaveBeenCalledWith();
});
