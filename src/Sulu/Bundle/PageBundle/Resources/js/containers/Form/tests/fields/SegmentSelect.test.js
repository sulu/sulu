// @flow
import React from 'react';
import {mount} from 'enzyme';
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

jest.mock('sulu-admin-bundle/utils', () => ({
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

test('Render a label and a SingleSelect for each granted webspace that has segments', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test'
        )
    );

    const segmentSelect = mount(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={{
                'webspace-1': 's',
            }}
        />
    );

    expect(segmentSelect.find('SingleSelect')).toHaveLength(2);
    expect(segmentSelect.render()).toMatchSnapshot();
});

test('Render only one label and SingleSelect for if options conatin a webspace', () => {
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

    const segmentSelect = mount(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={{}}
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');
    expect(segmentSelect.find('SingleSelect')).toHaveLength(1);
});

test('Pass correct props to SingleSelect', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test'
        )
    );

    const segmentSelect = mount(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={{
                'webspace-1': 's',
            }}
        />
    );

    segmentSelect.find('Select').at(0).instance().openOptionList();
    segmentSelect.update();

    expect(segmentSelect.find('SingleSelect').at(0).prop('disabled')).toEqual(true);
    expect(segmentSelect.find('SingleSelect').at(0).prop('value')).toEqual('s');
    expect(segmentSelect.find('SingleSelect').at(1).prop('disabled')).toEqual(true);
    expect(segmentSelect.find('SingleSelect').at(1).prop('value')).toEqual(undefined);

    expect(segmentSelect.find('Option').at(0).prop('children')).toEqual('sulu_admin.none_selected');
    expect(segmentSelect.find('Option').at(0).prop('value')).toEqual(undefined);
    expect(segmentSelect.find('Option').at(1).prop('children')).toEqual('Winter');
    expect(segmentSelect.find('Option').at(1).prop('value')).toEqual('w');
    expect(segmentSelect.find('Option').at(2).prop('children')).toEqual('Summer');
    expect(segmentSelect.find('Option').at(2).prop('value')).toEqual('s');
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

    const segmentSelect = mount(
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

    segmentSelect.find('SingleSelect').at(1).prop('onChange')('a');
    expect(changeSpy).toBeCalledWith({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
    expect(finishSpy).toBeCalledWith();
});
