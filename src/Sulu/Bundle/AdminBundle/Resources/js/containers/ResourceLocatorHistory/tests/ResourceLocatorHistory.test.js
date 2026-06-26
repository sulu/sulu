// @flow
import React from 'react';
import {act, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable} from 'mobx';
import ResourceLocatorHistory from '../ResourceLocatorHistory';
import ResourceListStore from '../../../stores/ResourceListStore';

let mockResourceListStoreInstances: Array<Object> = [];
let mockTableButtons: Array<Object> = [];

const mockReact = require('react');

jest.mock('../../../utils/Translator');

jest.mock('../../../stores/ResourceListStore', () => jest.fn(function() {
    this.deleteList = jest.fn();

    mockExtendObservable(this, {
        data: [],
        deleting: false,
        loading: true,
    });

    mockResourceListStoreInstances.push(this);
}));

jest.mock('../../../components/Button', () => jest.fn((props) => (
    mockReact.createElement(
        'button',
        {
            'data-disabled': props.disabled ? 'true' : 'false',
            'data-icon': props.icon,
            'data-skin': props.skin,
            disabled: props.disabled,
            onClick: props.onClick,
            type: 'button',
        },
        props.children || props.icon
    )
)));

jest.mock('../../../components/Dialog', () => jest.fn((props) => (
    props.open
        ? mockReact.createElement(
            'div',
            {'data-testid': 'dialog'},
            mockReact.createElement('span', {}, props.title),
            mockReact.createElement('div', {}, props.children),
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'dialog-cancel',
                    onClick: props.onCancel,
                    type: 'button',
                },
                props.cancelText
            ),
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'dialog-confirm',
                    onClick: props.onConfirm,
                    type: 'button',
                },
                props.confirmText
            )
        )
        : null
)));

jest.mock('../../../components/Loader', () => jest.fn(() => (
    mockReact.createElement('div', {'data-testid': 'loader'})
)));

jest.mock('../../../components/Overlay', () => jest.fn((props) => (
    props.open
        ? mockReact.createElement(
            'section',
            {
                'data-open': 'true',
                'data-testid': 'overlay',
            },
            mockReact.createElement('h2', {}, props.title),
            props.children,
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'overlay-confirm',
                    onClick: props.onConfirm,
                    type: 'button',
                },
                props.confirmText
            ),
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'overlay-close',
                    onClick: props.onClose,
                    type: 'button',
                },
                'Close'
            )
        )
        : null
)));

jest.mock('../../../components/Table', () => {
    const TableMock: any = jest.fn((props) => {
        mockTableButtons = props.buttons || [];

        return mockReact.createElement('table', {}, props.children);
    });

    TableMock.Header = (props) => mockReact.createElement(
        'thead',
        {},
        mockReact.createElement('tr', {}, props.children)
    );
    TableMock.HeaderCell = (props) => mockReact.createElement('th', {}, props.children);
    TableMock.Body = (props) => mockReact.createElement('tbody', {}, props.children);
    TableMock.Cell = (props) => mockReact.createElement('td', {}, props.children);
    TableMock.Row = (props) => mockReact.createElement(
        'tr',
        {},
        props.children,
        mockTableButtons.map((button) => (
            mockReact.createElement(
                'td',
                {key: button.icon},
                mockReact.createElement(
                    'button',
                    {
                        'aria-label': button.icon + '-' + props.id,
                        onClick: () => button.onClick(props.id),
                        type: 'button',
                    },
                    button.icon
                )
            )
        ))
    );

    return TableMock;
});

beforeEach(() => {
    jest.clearAllMocks();
    mockResourceListStoreInstances = [];
    mockTableButtons = [];
});

function renderResourceLocatorHistory(props: Object = {}) {
    return render(
        <ResourceLocatorHistory
            id={5}
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
            {...props}
        />
    );
}

function getResourceListStore() {
    return mockResourceListStoreInstances[0];
}

async function openHistoryOverlay() {
    await userEvent.click(screen.getByText('sulu_admin.show_history'));
}

function setHistoryData(data) {
    act(() => {
        getResourceListStore().loading = false;
        getResourceListStore().data = data;
    });
}

test('Pass props correctly to ResourceListStore', async() => {
    renderResourceLocatorHistory();

    expect(ResourceListStore).not.toHaveBeenCalled();

    await openHistoryOverlay();

    expect(ResourceListStore).toHaveBeenCalledWith('history_routes', {id: 5, webspace: 'sulu'});
});

test('Pass correct props to Button', () => {
    renderResourceLocatorHistory();

    const button = screen.getByText('sulu_admin.show_history');
    expect(button).toHaveAttribute('data-disabled', 'false');
    expect(button).toHaveAttribute('data-icon', 'su-process');
    expect(button).toHaveAttribute('data-skin', 'link');
});

test('Disable button if id is not set', () => {
    renderResourceLocatorHistory({id: undefined});

    expect(screen.getByText('sulu_admin.show_history')).toBeDisabled();
});

test('Show history routes in overlay', async() => {
    renderResourceLocatorHistory();

    expect(screen.queryByTestId('loader')).not.toBeInTheDocument();

    await openHistoryOverlay();
    expect(screen.getByTestId('loader')).toBeInTheDocument();

    setHistoryData([
        {
            id: 3,
            resourcelocator: 'sulu.io/test',
            created: '2019-04-10T13:06:16',
        },
        {
            id: 6,
            resourcelocator: 'sulu.io/testing',
            created: '2019-04-10T16:01:12',
        },
    ]);

    expect(await screen.findByText('sulu.io/test')).toBeInTheDocument();
    expect(screen.getByText('sulu.io/testing')).toBeInTheDocument();
    expect(screen.getByText((new Date('2019-04-10T13:06:16')).toLocaleString())).toBeInTheDocument();
});

test('Reload history routes each time overlay is opened', async() => {
    renderResourceLocatorHistory();

    expect(ResourceListStore).toHaveBeenCalledTimes(0);

    await openHistoryOverlay();
    expect(ResourceListStore).toHaveBeenCalledTimes(1);

    await openHistoryOverlay();
    expect(ResourceListStore).toHaveBeenCalledTimes(2);
});

test('Close overlay if button is clicked', async() => {
    renderResourceLocatorHistory();

    expect(screen.queryByTestId('overlay')).not.toBeInTheDocument();

    await openHistoryOverlay();
    expect(screen.getByTestId('overlay')).toBeInTheDocument();

    await userEvent.click(screen.getByLabelText('overlay-confirm'));

    expect(screen.queryByTestId('overlay')).not.toBeInTheDocument();
});

test('Do not delete if confirmation dialog is cancelled', async() => {
    renderResourceLocatorHistory();

    await openHistoryOverlay();
    setHistoryData([
        {
            id: 3,
            resourcelocator: 'sulu.io/test',
            created: '2019-04-10T13:06:16',
        },
    ]);

    await userEvent.click(await screen.findByLabelText('su-trash-alt-3'));

    expect(screen.getByTestId('dialog')).toBeInTheDocument();

    await userEvent.click(screen.getByLabelText('dialog-cancel'));

    expect(screen.queryByTestId('dialog')).not.toBeInTheDocument();
    expect(getResourceListStore().deleteList).not.toHaveBeenCalled();
});

test('Delete if confirmation dialog is confirmed', async() => {
    renderResourceLocatorHistory();

    await openHistoryOverlay();
    setHistoryData([
        {
            id: 3,
            resourcelocator: 'sulu.io/test',
            created: '2019-04-10T13:06:16',
        },
    ]);

    let resolveDeleteList;
    const deleteListPromise = new Promise((resolve) => {
        resolveDeleteList = resolve;
    });
    getResourceListStore().deleteList.mockReturnValue(deleteListPromise);

    await userEvent.click(await screen.findByLabelText('su-trash-alt-3'));

    expect(screen.getByTestId('dialog')).toBeInTheDocument();

    await userEvent.click(screen.getByLabelText('dialog-confirm'));

    expect(screen.getByTestId('dialog')).toBeInTheDocument();
    expect(getResourceListStore().deleteList).toHaveBeenCalledWith([3]);

    act(() => {
        resolveDeleteList();
    });

    await waitFor(() => expect(screen.queryByTestId('dialog')).not.toBeInTheDocument());
});
