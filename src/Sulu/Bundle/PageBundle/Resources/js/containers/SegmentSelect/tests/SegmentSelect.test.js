// @flow
import {render} from '@testing-library/react';
import React from 'react';
import webspaceStore from '../../../stores/webspaceStore';
import SegmentSelect from '../../SegmentSelect';
import WebspaceSegmentSelect from '../../SegmentSelect/WebspaceSegmentSelect';

jest.mock('../../SegmentSelect/WebspaceSegmentSelect', () => jest.fn(() => null));

function createWebspace(webspace) {
    return ({
        _permissions: {view: true},
        ...webspace,
    }: any);
}

function setWebspaces(webspaces) {
    webspaceStore.setWebspaces(webspaces.map(createWebspace));
}

beforeEach(() => {
    jest.clearAllMocks();
    webspaceStore.setWebspaces([]);
});

test('Render a WebspaceSegmentSelect for each granted webspace that has segments', () => {
    setWebspaces([
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
    ]);

    render(
        <SegmentSelect
            disabled={false}
            onChange={jest.fn()}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
        />
    );

    expect(WebspaceSegmentSelect).toHaveBeenCalledTimes(2);
});

test('Render label without webspace name if only one webspace has segments', () => {
    setWebspaces([
        {
            name: 'Webspace One',
            key: 'webspace-1',
            segments: [
                {key: 'w', title: 'Winter'},
                {key: 's', title: 'Summer'},
            ],
        },
    ]);

    render(
        <SegmentSelect
            disabled={false}
            onChange={jest.fn()}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
        />
    );

    const [webspaceSegmentSelectProps] = (WebspaceSegmentSelect: any).mock.calls[0];
    expect(webspaceSegmentSelectProps.webspaceNameVisible).toEqual(false);
});

test('Render only one WebspaceSegmentSelect if options contain a webspace', () => {
    const webspace = {
        name: 'Webspace One',
        key: 'webspace-1',
        segments: [
            {key: 'w', title: 'Winter'},
            {key: 's', title: 'Summer'},
        ],
    };
    setWebspaces([webspace]);

    render(
        <SegmentSelect
            disabled={false}
            onChange={jest.fn()}
            value={{}}
            webspace="webspace-1"
        />
    );

    expect(WebspaceSegmentSelect).toHaveBeenCalledTimes(1);
});

test('Pass correct props to WebspaceSegmentSelect', () => {
    setWebspaces([
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
    ]);

    render(
        <SegmentSelect
            disabled={true}
            onChange={jest.fn()}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
        />
    );

    const [firstWebspaceSegmentSelectProps] = (WebspaceSegmentSelect: any).mock.calls[0];
    const [secondWebspaceSegmentSelectProps] = (WebspaceSegmentSelect: any).mock.calls[1];

    expect(firstWebspaceSegmentSelectProps.disabled).toEqual(true);
    expect(firstWebspaceSegmentSelectProps.value).toEqual('s');
    expect(secondWebspaceSegmentSelectProps.disabled).toEqual(true);
    expect(secondWebspaceSegmentSelectProps.value).toEqual(undefined);
});

test('Call onChange if the value is changed', () => {
    const changeSpy = jest.fn();

    setWebspaces([
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
    ]);

    render(
        <SegmentSelect
            disabled={true}
            onChange={changeSpy}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
        />
    );

    const [, secondWebspaceSegmentSelectProps] = (WebspaceSegmentSelect: any).mock.calls;
    secondWebspaceSegmentSelectProps[0].onChange('webspace-3', 'a');

    expect(changeSpy).toBeCalledWith({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
});
