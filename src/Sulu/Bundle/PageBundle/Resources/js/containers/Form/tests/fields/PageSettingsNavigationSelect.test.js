// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import webspaceStore from '../../../../stores/webspaceStore';
import PageSettingsNavigationSelect from '../../fields/PageSettingsNavigationSelect';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/webspaceStore', () => ({
    getWebspace: jest.fn(),
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector() {
    return ({
        options: {webspace: 'sulu_io'},
    }: any);
}

test('Pass correct props to MultiSelect', () => {
    const webspace = {
        navigations: [
            {key: 'main', title: 'Main Navigation'},
            {key: 'footer', title: 'Footer Navigation'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    render(
        <PageSettingsNavigationSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector()}
            value={['footer']}
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');
    expect(screen.getByRole('button', {name: /Footer Navigation/})).toBeDisabled();
});

test('Call onChange and onBlur if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const webspace = {
        navigations: [
            {key: 'main', title: 'Main Navigation'},
            {key: 'footer', title: 'Footer Navigation'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    render(
        <PageSettingsNavigationSelect
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={['footer']}
        />
    );

    await user.click(screen.getByRole('button', {name: /Footer Navigation/}));
    await user.click(screen.getByRole('button', {name: 'Main Navigation'}));

    expect(changeSpy).toBeCalledWith(['footer', 'main']);
    expect(finishSpy).toBeCalledWith();
});
