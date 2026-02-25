// @flow
import React from 'react';
import {render} from '@testing-library/react';
import getMockCallArg from 'sulu-admin-bundle/utils/TestHelper/getMockCallArg';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import webspaceStore from '../../../stores/webspaceStore';
import SegmentSelect from '../../SegmentSelect';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/webspaceStore', () => ({
    grantedWebspaces: [],
    getWebspace: jest.fn(),
}));

jest.mock('../WebspaceSegmentSelect', () => jest.fn(({value, webspace, webspaceNameVisible}) => (
    <div data-testid={'webspace-segment-select-' + webspace.key}>
        {webspaceNameVisible ? 'visible' : 'hidden'}-{value || 'none'}
    </div>
)));

const webspaceSegmentSelectModule = ((jest.requireMock('../WebspaceSegmentSelect'): any): {
    mock: {calls: Array<[Object]>},
    ...
});
const webspaceSegmentSelectMock = webspaceSegmentSelectModule;
const mockedWebspaceStore: any = webspaceStore;

const grantedWebspaces = [
    {
        key: 'webspace-1',
        name: 'Webspace One',
        segments: [
            {key: 'w', title: 'Winter'},
            {key: 's', title: 'Summer'},
        ],
    },
    {
        key: 'webspace-2',
        name: 'Webspace Two',
        segments: [],
    },
    {
        key: 'webspace-3',
        name: 'Webspace Three',
        segments: [
            {key: 'a', title: 'Autumn'},
            {key: 'p', title: 'Spring'},
        ],
    },
];

beforeEach(() => {
    jest.clearAllMocks();
    mockedWebspaceStore.grantedWebspaces = [];
});

test('Render a label and a SingleSelect for each granted webspace that has segments', () => {
    mockedWebspaceStore.grantedWebspaces = grantedWebspaces;

    const {asFragment} = render(
        <SegmentSelect
            disabled={false}
            onChange={jest.fn()}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
        />
    );

    expect(webspaceSegmentSelectMock).toHaveBeenCalledTimes(2);
    expect(getMockCallArg(webspaceSegmentSelectMock, 0, 0)).toEqual(expect.objectContaining({
        disabled: false,
        value: 's',
        webspace: grantedWebspaces[0],
        webspaceNameVisible: true,
    }));
    expect(getMockCallArg(webspaceSegmentSelectMock, 1, 0)).toEqual(expect.objectContaining({
        disabled: false,
        value: undefined,
        webspace: grantedWebspaces[2],
        webspaceNameVisible: true,
    }));
    expect(asFragment()).toMatchSnapshot();
});

test('Render a label without webspace name if only one webspace has segments', () => {
    mockedWebspaceStore.grantedWebspaces = [grantedWebspaces[0]];

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

    expect(webspaceSegmentSelectMock).toHaveBeenCalledTimes(1);
    expect(getLatestMockProps(webspaceSegmentSelectMock)).toEqual(expect.objectContaining({
        webspaceNameVisible: false,
    }));
});

test('Render only one label and SingleSelect if options contain a webspace', () => {
    webspaceStore.getWebspace.mockReturnValue(grantedWebspaces[0]);

    render(
        <SegmentSelect
            disabled={false}
            onChange={jest.fn()}
            value={{}}
            webspace="webspace-1"
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('webspace-1');
    expect(webspaceSegmentSelectMock).toHaveBeenCalledTimes(1);
    expect(getLatestMockProps(webspaceSegmentSelectMock)).toEqual(expect.objectContaining({
        webspace: grantedWebspaces[0],
        webspaceNameVisible: false,
    }));
});

test('Pass correct props to SingleSelect', () => {
    mockedWebspaceStore.grantedWebspaces = grantedWebspaces;

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

    expect(webspaceSegmentSelectMock).toHaveBeenCalledTimes(2);
    expect(getMockCallArg(webspaceSegmentSelectMock, 0, 0)).toEqual(expect.objectContaining({
        disabled: true,
        value: 's',
        webspace: grantedWebspaces[0],
    }));
    expect(getMockCallArg(webspaceSegmentSelectMock, 1, 0)).toEqual(expect.objectContaining({
        disabled: true,
        value: undefined,
        webspace: grantedWebspaces[2],
    }));
});

test('Call onChange if the value is changed', () => {
    const changeSpy = jest.fn();
    mockedWebspaceStore.grantedWebspaces = grantedWebspaces;

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

    getMockCallArg(webspaceSegmentSelectMock, 1, 0).onChange('webspace-3', 'a');

    expect(changeSpy).toBeCalledWith({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
});
