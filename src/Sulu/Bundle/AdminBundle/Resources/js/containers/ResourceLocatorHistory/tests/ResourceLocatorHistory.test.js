// @flow
import React from 'react';
import {extendObservable as mockExtendObservable} from 'mobx';
import {act, render, screen, waitFor, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceLocatorHistory from '../ResourceLocatorHistory';
import ResourceListStore from '../../../stores/ResourceListStore';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/ResourceListStore', () => jest.fn(function() {
    this.deleteList = jest.fn();

    mockExtendObservable(this, {
        data: [],
        deleting: false,
        loading: true,
    });
}));

function getFirstResourceListStoreMockInstance() {
    return (ResourceListStore: any).mock.instances[0];
}

function getShowHistoryButton() {
    return screen.getByRole('button', {name: /sulu_admin\.show_history/i});
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass props correctly to ResourceListStore', async() => {
    const user = userEvent.setup();
    render(
        <ResourceLocatorHistory
            id={5}
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    expect(ResourceListStore).not.toBeCalled();

    await user.click(getShowHistoryButton());
    expect(ResourceListStore).toBeCalledWith('history_routes', {id: 5, webspace: 'sulu'});
});

test('Pass correct props to Button', () => {
    render(
        <ResourceLocatorHistory
            id={5}
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    const showHistoryButton = getShowHistoryButton();

    expect(showHistoryButton).toBeEnabled();
    expect(showHistoryButton.className).toEqual(expect.stringContaining('link'));
    expect(screen.getByLabelText('su-process')).toBeInTheDocument();
});

test('Disable button if id is not set', () => {
    render(
        <ResourceLocatorHistory
            id={undefined}
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    expect(getShowHistoryButton()).toBeDisabled();
});

test('Show history routes in overlay', async() => {
    const user = userEvent.setup();
    render(
        <ResourceLocatorHistory
            id={5}
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    expect(screen.queryByRole('table')).not.toBeInTheDocument();

    await user.click(getShowHistoryButton());
    expect(screen.queryByRole('table')).not.toBeInTheDocument();

    const resourceListStore = getFirstResourceListStoreMockInstance();

    act(() => {
        resourceListStore.loading = false;
        resourceListStore.data = [
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
        ];
    });

    expect(await screen.findByRole('table')).toBeInTheDocument();
    expect(screen.getByText('sulu.io/test')).toBeInTheDocument();
    expect(screen.getByText('sulu.io/testing')).toBeInTheDocument();
});

test('Reload history routes each time overlay is opened', async() => {
    const user = userEvent.setup();
    render(
        <ResourceLocatorHistory
            id={5}
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    expect(ResourceListStore).toBeCalledTimes(0);

    await user.click(getShowHistoryButton());
    expect(ResourceListStore).toBeCalledTimes(1);

    await user.click(getShowHistoryButton());
    expect(ResourceListStore).toBeCalledTimes(2);
});

test('Close overlay if button is clicked', async() => {
    const user = userEvent.setup();
    render(
        <ResourceLocatorHistory
            id={5}
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    expect(screen.queryByText('sulu_admin.history')).not.toBeInTheDocument();

    await user.click(getShowHistoryButton());
    expect(await screen.findByText('sulu_admin.history')).toBeInTheDocument();

    const overlay = screen.getByText('sulu_admin.history').closest('section');
    if (!overlay) {
        throw new Error('Expected overlay element to exist');
    }
    const overlayContainer = overlay.closest('.container');
    if (!overlayContainer) {
        throw new Error('Expected overlay container element to exist');
    }

    expect(overlayContainer.className).toEqual(expect.stringContaining('isDown'));

    await user.click(within(overlay).getByRole('button', {name: 'sulu_admin.ok'}));
    await waitFor(() => expect(overlayContainer.className).not.toEqual(expect.stringContaining('isDown')));
});

test('Do not delete if confirmation dialog is cancelled', async() => {
    const user = userEvent.setup();
    render(
        <ResourceLocatorHistory
            id={5}
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    await user.click(getShowHistoryButton());

    const resourceListStore = getFirstResourceListStoreMockInstance();

    act(() => {
        resourceListStore.loading = false;
        resourceListStore.data = [
            {
                id: 3,
                resourcelocator: 'sulu.io/test',
                created: '2019-04-10T13:06:16',
            },
        ];
    });

    expect(await screen.findByText('sulu.io/test')).toBeInTheDocument();
    const deleteButton = document.querySelector('tbody button');
    if (!deleteButton) {
        throw new Error('Expected delete button to exist');
    }

    await user.click(deleteButton);

    const warningText = 'sulu_admin.resource_locator_history_delete_warning';
    const warning = await screen.findByText(warningText);
    const dialog = warning.closest('section');
    if (!dialog) {
        throw new Error('Expected dialog element to exist');
    }
    const dialogContainer = warning.closest('.dialogContainer');
    if (!dialogContainer) {
        throw new Error('Expected dialog container to exist');
    }
    expect(dialogContainer.className).toEqual(expect.stringContaining('open'));

    await user.click(within(dialog).getByRole('button', {name: 'sulu_admin.cancel'}));
    await waitFor(() => expect(dialogContainer.className).not.toEqual(expect.stringContaining('open')));

    expect(resourceListStore.deleteList).not.toBeCalled();
});

test('Delete if confirmation dialog is confirmed', async() => {
    const user = userEvent.setup();
    render(
        <ResourceLocatorHistory
            id={5}
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    await user.click(getShowHistoryButton());

    const resourceListStore = getFirstResourceListStoreMockInstance();

    act(() => {
        resourceListStore.loading = false;
        resourceListStore.data = [
            {
                id: 3,
                resourcelocator: 'sulu.io/test',
                created: '2019-04-10T13:06:16',
            },
        ];
    });

    let resolveDeleteList;
    const deleteListPromise = new Promise<void>((resolve) => {
        resolveDeleteList = resolve;
    });
    resourceListStore.deleteList.mockReturnValue(deleteListPromise);

    expect(await screen.findByText('sulu.io/test')).toBeInTheDocument();
    const deleteButton = document.querySelector('tbody button');
    if (!deleteButton) {
        throw new Error('Expected delete button to exist');
    }

    await user.click(deleteButton);

    const warningText = 'sulu_admin.resource_locator_history_delete_warning';
    const warning = await screen.findByText(warningText);
    const dialog = warning.closest('section');
    if (!dialog) {
        throw new Error('Expected dialog element to exist');
    }
    const dialogContainer = warning.closest('.dialogContainer');
    if (!dialogContainer) {
        throw new Error('Expected dialog container to exist');
    }
    expect(dialogContainer.className).toEqual(expect.stringContaining('open'));

    await user.click(within(dialog).getByRole('button', {name: 'sulu_admin.ok'}));
    expect(dialogContainer.className).toEqual(expect.stringContaining('open'));

    expect(resourceListStore.deleteList).toBeCalledWith([3]);
    if (!resolveDeleteList) {
        throw new Error('Expected resolver to exist');
    }
    resolveDeleteList();

    await deleteListPromise;
    await waitFor(() => expect(dialogContainer.className).not.toEqual(expect.stringContaining('open')));
});
