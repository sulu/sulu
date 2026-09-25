// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import webspaceStore from '../../../../stores/webspaceStore';
import PageSettingsNavigationSelect from '../../fields/PageSettingsNavigationSelect';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(formStore) {
        this.options = formStore.options;
    }),
    ResourceFormStore: jest.fn(function(resourceStore, formKey, options) {
        this.options = options;
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../../stores/webspaceStore', () => ({
    getWebspace: jest.fn(),
}));

test('Pass correct props to MultiSelect', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'}
        )
    );

    const webspace = {
        navigations: [
            {key: 'main', title: 'Main Navigation'},
            {key: 'footer', title: 'Footer Navigation'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    const {rerender} = render(
        <PageSettingsNavigationSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={['footer']}
        />
    );

    expect(webspaceStore.getWebspace).toHaveBeenCalledWith('sulu_io');
    expect(screen.getByRole('button', {name: /Footer Navigation/})).toBeDisabled();

    rerender(
        <PageSettingsNavigationSelect
            {...fieldTypeDefaultProps}
            disabled={false}
            formInspector={formInspector}
            value={['footer']}
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));

    expect(screen.getByRole('button', {name: 'Main Navigation'})).toBeInTheDocument();
    expect(screen.getAllByRole('button', {name: /Footer Navigation/})).toHaveLength(2);
});

test('Call onChange and onBlur if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'}
        )
    );

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
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={['footer']}
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: 'Main Navigation'}));

    expect(changeSpy).toHaveBeenCalledWith(['footer', 'main']);
    expect(finishSpy).toHaveBeenCalledWith();
});
