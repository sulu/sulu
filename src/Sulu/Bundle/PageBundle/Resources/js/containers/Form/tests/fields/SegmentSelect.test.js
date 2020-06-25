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

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/webspaceStore', () => ({
    getWebspace: jest.fn(),
}));

test('Pass correct props to MultiSelect', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'sulu_io'},
            {webspace: 'sulu_io'}
        )
    );

    const webspace = {
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
            value="s"
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');

    expect(segmentSelect.find('SingleSelect').prop('disabled')).toEqual(true);
    expect(segmentSelect.find('SingleSelect').prop('value')).toEqual('s');
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
            'test',
            {webspace: 'sulu_io'},
            {webspace: 'sulu_io'}
        )
    );

    const webspace = {
        segments: [
            {key: 'w', title: 'Winter'},
            {key: 's', title: 'Summer'},
        ],
    };
    webspaceStore.getWebspace.mockReturnValue(webspace);

    const segmentSelect = shallow(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value="w"
        />
    );

    segmentSelect.find('SingleSelect').prop('onChange')('s');
    expect(changeSpy).toBeCalledWith('s');
    expect(finishSpy).toBeCalledWith();
});
