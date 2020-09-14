// @flow
import React from 'react';
import {shallow} from 'enzyme';
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

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

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

    const segmentSelect = shallow(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={{}}
        />
    );

    expect(segmentSelect.find('SegmentSelect').prop('disabled')).toEqual(true);
    expect(segmentSelect.find('SegmentSelect').prop('value')).toEqual({});
    expect(segmentSelect.find('SegmentSelect').prop('webspace')).toEqual('sulu_io');
});

test('Call onChange and onBlur if the value is changed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test'
        )
    );

    const segmentSelect = shallow(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={{
                'webspace-1': 's',
            }}
        />
    );

    segmentSelect.find('SegmentSelect').prop('onChange')({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
    expect(changeSpy).toBeCalledWith({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
    expect(finishSpy).toBeCalledWith();
});
