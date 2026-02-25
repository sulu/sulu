// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import SegmentSelect from '../../fields/SegmentSelect';

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/webspaceStore', () => ({
    __esModule: true,
    default: {
        getWebspace: jest.fn(),
        grantedWebspaces: [],
    },
}));

const webspaceStore = jest.requireMock('../../../../stores/webspaceStore').default;

test('Pass correct props to SegmentSelect', () => {
    const formInspector: any = {
        metadataOptions: {
            webspace: 'sulu_io',
        },
    };
    webspaceStore.getWebspace.mockReturnValue({
        key: 'sulu_io',
        name: 'Sulu.io',
        segments: [
            {key: 's', title: 'Segment S'},
        ],
    });

    render(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={{}}
        />
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu_io');
    expect(screen.getByRole('button')).toBeDisabled();
    expect(screen.getByText('sulu_admin.segment')).toBeInTheDocument();
});

test('Call onChange and onBlur if the value is changed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector: any = {
        metadataOptions: {},
    };
    webspaceStore.grantedWebspaces = [
        {
            key: 'webspace-3',
            name: 'Webspace 3',
            segments: [
                {key: 'a', title: 'Segment A'},
            ],
        },
    ];

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

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: 'Segment A'}));

    expect(changeSpy).toBeCalledWith({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
    expect(finishSpy).toBeCalledWith();
});
