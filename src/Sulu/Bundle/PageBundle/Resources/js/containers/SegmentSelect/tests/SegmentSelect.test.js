// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import webspaceStore from '../../../stores/webspaceStore';
import SegmentSelect from '../../SegmentSelect';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../stores/webspaceStore', () => ({
    getWebspace: jest.fn(),
}));

test('Render a label and a SingleSelect snapshot for each granted webspace that has segments', () => {
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

    expect(screen.getAllByLabelText('su-angle-down')).toHaveLength(2);
    expect(asFragment()).toMatchSnapshot();
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

    expect(screen.getAllByLabelText('su-angle-down')).toHaveLength(2);
    expect(asFragment()).toMatchSnapshot();
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

    render(
        <SegmentSelect
            disabled={false}
            onChange={jest.fn()}
            value={{}}
            webspace="webspace-1"
        />
    );

    expect(webspaceStore.getWebspace).toHaveBeenCalledWith('webspace-1');
    expect(screen.getAllByLabelText('su-angle-down')).toHaveLength(1);
});

test('Pass correct props to SingleSelect', async() => {
    const user = userEvent.setup();

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

    const {rerender} = render(
        <SegmentSelect
            disabled={true}
            onChange={jest.fn()}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
        />
    );

    expect(screen.getByRole('button', {name: /Summer/})).toBeDisabled();
    expect(screen.getByRole('button', {name: /sulu_admin.none_selected/})).toBeDisabled();

    rerender(
        <SegmentSelect
            disabled={false}
            onChange={jest.fn()}
            value={{
                'webspace-1': 's',
            }}
            webspace={undefined}
        />
    );

    await user.click(screen.getAllByLabelText('su-angle-down')[0]);

    expect(screen.getByRole('button', {name: 'Winter'})).toBeInTheDocument();
    expect(screen.getAllByRole('button', {name: /Summer/})).toHaveLength(2);
    expect(screen.getAllByRole('button', {name: /sulu_admin.none_selected/})).toHaveLength(2);
});

test('Call onChange if the value is changed', async() => {
    const user = userEvent.setup();
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

    await user.click(screen.getAllByLabelText('su-angle-down')[1]);
    await user.click(screen.getByRole('button', {name: 'Autumn'}));

    expect(changeSpy).toHaveBeenCalledWith({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
});
