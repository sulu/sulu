// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import webspaceStore from '../../../../stores/webspaceStore';
import SegmentSelect from '../../fields/SegmentSelect';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(formStore) {
        this.options = formStore.options;
        this.metadataOptions = formStore.metadataOptions;
    }),
    ResourceFormStore: jest.fn(function(resourceStore, formKey, options, metadataOptions) {
        this.options = options;
        this.metadataOptions = metadataOptions;
    }),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../../stores/webspaceStore', () => ({
    getWebspace: jest.fn(),
    grantedWebspaces: [
        {
            name: 'Webspace One',
            key: 'webspace-1',
            segments: [
                {key: 'w', title: 'Winter'},
                {key: 's', title: 'Summer'},
            ],
        },
        {
            name: 'Webspace Two',
            key: 'webspace-2',
            segments: [],
        },
        {
            name: 'Webspace Three',
            key: 'webspace-3',
            segments: [
                {key: 'a', title: 'Autumn'},
                {key: 'p', title: 'Spring'},
            ],
        },
    ],
}));

test('Pass correct props to SegmentSelect', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'},
            {webspace: 'sulu_io'}
        )
    );

    const webspace = {
        name: 'Webspace One',
        key: 'webspace-1',
        segments: [
            {key: 'w', title: 'Winter'},
            {key: 's', title: 'Summer'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    render(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={{}}
        />
    );

    expect(webspaceStore.getWebspace).toHaveBeenCalledWith('sulu_io');
    expect(screen.getByText('sulu_admin.segment')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /sulu_admin.none_selected/})).toBeDisabled();
});

test('Call onChange and onBlur if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test'
        )
    );

    render(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            disabled={false}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={{
                'webspace-1': 's',
            }}
        />
    );

    await user.click(screen.getAllByLabelText('su-angle-down')[1]);
    await user.click(screen.getByRole('button', {name: 'Autumn'}));

    expect(changeSpy).toHaveBeenCalledWith({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
    expect(finishSpy).toHaveBeenCalledWith();
});
