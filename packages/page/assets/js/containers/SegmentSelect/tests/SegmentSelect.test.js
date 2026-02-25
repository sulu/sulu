// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import webspaceStore from '../../../stores/webspaceStore';
import SegmentSelect from '../../SegmentSelect';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/webspaceStore', () => ({
    grantedWebspaces: [],
    getWebspace: jest.fn(),
}));
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

    expect(screen.getByText('Webspace One - sulu_admin.segment')).toBeInTheDocument();
    expect(screen.getByText('Webspace Three - sulu_admin.segment')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /Summer/})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /sulu_admin.none_selected/})).toBeInTheDocument();
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

    expect(screen.getByText('sulu_admin.segment')).toBeInTheDocument();
    expect(screen.queryByText('Webspace One - sulu_admin.segment')).not.toBeInTheDocument();
    expect(screen.getAllByRole('button')).toHaveLength(1);
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
    expect(screen.getByText('sulu_admin.segment')).toBeInTheDocument();
    expect(screen.getAllByRole('button')).toHaveLength(1);
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

    expect(screen.getAllByRole('button')).toHaveLength(2);
    expect(screen.getAllByRole('button')[0]).toBeDisabled();
    expect(screen.getAllByRole('button')[1]).toBeDisabled();
});

test('Call onChange if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    mockedWebspaceStore.grantedWebspaces = grantedWebspaces;

    render(
        <SegmentSelect
            disabled={false}
            onChange={changeSpy}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
        />
    );

    const secondWebspaceSectionLabel = screen.getByText('Webspace Three - sulu_admin.segment');
    const secondWebspaceSection = secondWebspaceSectionLabel.closest('div');
    if (!secondWebspaceSection) {
        throw new Error('Expected second webspace section to exist');
    }

    await user.click(within(secondWebspaceSection).getByRole('button'));
    await user.click(screen.getByRole('button', {name: 'Autumn'}));

    expect(changeSpy).toBeCalledWith({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
});
