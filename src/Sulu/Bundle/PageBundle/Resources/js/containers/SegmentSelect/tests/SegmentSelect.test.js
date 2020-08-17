// @flow
import React from 'react';
import {mount} from 'enzyme';
import webspaceStore from '../../../stores/webspaceStore';
import SegmentSelect from '../../SegmentSelect';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/webspaceStore', () => ({
    getWebspace: jest.fn(),
}));

test('Render a label and a SingleSelect for each granted webspace that has segments', () => {
    // $FlowFixMe
    webspaceStore.grantedWebspaces = [
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
    ];

    const segmentSelect = mount(
        <SegmentSelect
            disabled={false}
            onChange={jest.fn()}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
        />
    );

    expect(segmentSelect.find('SingleSelect')).toHaveLength(2);
    expect(segmentSelect.render()).toMatchSnapshot();
});

test('Render a label without webspace name if only one webspace has segments', () => {
    // $FlowFixMe
    webspaceStore.grantedWebspaces = [
        {
            name: 'Webspace One',
            key: 'webspace-1',
            segments: [
                {key: 'w', title: 'Winter'},
                {key: 's', title: 'Summer'},
            ],
        },
    ];

    const segmentSelect = mount(
        <SegmentSelect
            disabled={false}
            onChange={jest.fn()}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
        />
    );

    expect(segmentSelect.find('label')).toHaveLength(1);
    expect(segmentSelect.find('label').text()).toEqual('sulu_admin.segment');
});

test('Render a label and a SingleSelect for each granted webspace that has segments', () => {
    // $FlowFixMe
    webspaceStore.grantedWebspaces = [
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
    ];

    const segmentSelect = mount(
        <SegmentSelect
            disabled={false}
            onChange={jest.fn()}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
        />
    );

    expect(segmentSelect.find('SingleSelect')).toHaveLength(2);
    expect(segmentSelect.render()).toMatchSnapshot();
});

test('Render only one label and SingleSelect if options contain a webspace', () => {
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
            disabled={false}
            onChange={jest.fn()}
            value={{}}
            webspace="webspace-1"
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('webspace-1');
    expect(segmentSelect.find('SingleSelect')).toHaveLength(1);
});

test('Pass correct props to SingleSelect', () => {
    // $FlowFixMe
    webspaceStore.grantedWebspaces = [
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
    ];

    const segmentSelect = mount(
        <SegmentSelect
            disabled={true}
            onChange={jest.fn()}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
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

test('Call onChange if the value is changed', () => {
    const changeSpy = jest.fn();

    // $FlowFixMe
    webspaceStore.grantedWebspaces = [
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
    ];

    const segmentSelect = mount(
        <SegmentSelect
            disabled={true}
            onChange={changeSpy}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
        />
    );

    segmentSelect.find('SingleSelect').at(1).prop('onChange')('a');
    expect(changeSpy).toBeCalledWith({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
});
