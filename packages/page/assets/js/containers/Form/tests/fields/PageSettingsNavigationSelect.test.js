// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import webspaceStore from '../../../../stores/webspaceStore';
import PageSettingsNavigationSelect from '../../fields/PageSettingsNavigationSelect';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/webspaceStore', () => ({
    getWebspace: jest.fn(),
}));

test('Pass correct props to MultiSelect', () => {
    const webspace = {
        navigations: [
            {key: 'main', title: 'Main Navigation'},
            {key: 'footer', title: 'Footer Navigation'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    const formInspector: any = {
        options: {
            webspace: 'sulu_io',
        },
    };

    render(
        <PageSettingsNavigationSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={['footer']}
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');
    expect(screen.getByRole('button')).toBeDisabled();
    expect(screen.getByText('Footer Navigation')).toBeInTheDocument();
});

test('Render available navigations as options', async() => {
    const user = userEvent.setup();
    const webspace = {
        navigations: [
            {key: 'main', title: 'Main Navigation'},
            {key: 'footer', title: 'Footer Navigation'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    const formInspector: any = {
        options: {
            webspace: 'sulu_io',
        },
    };

    render(
        <PageSettingsNavigationSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={['footer']}
        />
    );

    await user.click(screen.getByRole('button'));

    expect(await screen.findByRole('button', {name: /Main Navigation/})).toBeInTheDocument();
    expect(screen.getAllByRole('button', {name: /Footer Navigation/})).toHaveLength(2);
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

    const formInspector: any = {
        options: {
            webspace: 'sulu_io',
        },
    };

    render(
        <PageSettingsNavigationSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={['footer']}
        />
    );

    await user.click(screen.getByRole('button'));
    await user.click(await screen.findByRole('button', {name: /Main Navigation/}));

    expect(changeSpy).toBeCalledWith(['footer', 'main']);
    expect(finishSpy).toBeCalledWith();
});
