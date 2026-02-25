// @flow
import React from 'react';
import {extendObservable as mockExtendObservable} from 'mobx';
import {fireEvent, render, screen, waitFor, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceListStore from 'sulu-admin-bundle/stores/ResourceListStore';
import ResourceLocatorHistory from '../ResourceLocatorHistory';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/ResourceListStore', () => jest.fn(function() {
    this.deleteList = jest.fn(() => Promise.resolve());

    mockExtendObservable(this, {
        data: [],
        deleting: false,
        loading: true,
    });
}));

const resourceListStoreMock: any = ResourceListStore;

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass props correctly to ResourceListStore', async() => {
    const user = userEvent.setup();
    render(
        <ResourceLocatorHistory
            options={{webspace: 'sulu', resourceKey: 'text', resourceId: 5}}
            resourceKey="history_routes"
        />
    );

    expect(ResourceListStore).not.toBeCalled();

    await user.click(screen.getByRole('button', {name: /sulu_admin\.show_history/}));
    expect(ResourceListStore).toBeCalledWith('history_routes', {webspace: 'sulu', resourceKey: 'text', resourceId: 5});
});

test('Pass correct props to Button', () => {
    render(
        <ResourceLocatorHistory
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    const showHistoryButton = screen.getByRole('button', {name: /sulu_admin\.show_history/});
    expect(within(showHistoryButton).getByLabelText('su-process')).toBeInTheDocument();
    expect(showHistoryButton).toBeEnabled();
});

test('Disable button if disabled isset', () => {
    render(
        <ResourceLocatorHistory
            disabled={true}
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    expect(screen.getByRole('button', {name: /sulu_admin\.show_history/})).toBeDisabled();
});

test('Show history routes in overlay', async() => {
    const user = userEvent.setup();
    const {asFragment} = render(
        <ResourceLocatorHistory
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    await user.click(screen.getByRole('button', {name: /sulu_admin\.show_history/}));
    expect(screen.getByText('sulu_admin.history')).toBeInTheDocument();
    expect(document.querySelector('.spinner')).toBeInTheDocument();

    const resourceListStore = resourceListStoreMock.mock.instances[0];
    resourceListStore.loading = false;
    resourceListStore.data = [
        {
            id: 3,
            slug: '/test',
            created: '2019-04-10T13:06:16',
        },
        {
            id: 6,
            slug: '/testing',
            created: '2019-04-10T16:01:12',
        },
    ];

    expect(await screen.findByText('/test')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Reload history routes each time overlay is opened', async() => {
    const user = userEvent.setup();
    render(
        <ResourceLocatorHistory
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    expect(ResourceListStore).toBeCalledTimes(0);

    await user.click(screen.getByRole('button', {name: /sulu_admin\.show_history/}));
    expect(ResourceListStore).toBeCalledTimes(1);

    await user.click(screen.getByRole('button', {name: /sulu_admin\.show_history/}));
    expect(ResourceListStore).toBeCalledTimes(2);
});

test('Close overlay if button is clicked', async() => {
    const user = userEvent.setup();
    render(
        <ResourceLocatorHistory
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    expect(screen.queryByText('sulu_admin.history')).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: /sulu_admin\.show_history/}));
    expect(screen.getByText('sulu_admin.history')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    const overlayContainer = screen.getByText('sulu_admin.history').closest('.container');
    if (!overlayContainer) {
        throw new Error('Expected overlay container to exist');
    }

    fireEvent.transitionEnd(overlayContainer);
    await waitFor(() => expect(screen.queryByText('sulu_admin.history')).not.toBeInTheDocument());
});

test('Do not delete if confirmation dialog is cancelled', async() => {
    const user = userEvent.setup();
    render(
        <ResourceLocatorHistory
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    await user.click(screen.getByRole('button', {name: /sulu_admin\.show_history/}));

    const resourceListStore = resourceListStoreMock.mock.instances[0];
    resourceListStore.loading = false;
    resourceListStore.data = [
        {
            id: 3,
            slug: '/test',
            created: '2019-04-10T13:06:16',
        },
    ];

    expect(await screen.findByText('/test')).toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));

    const dialog = screen.getByText('sulu_admin.resource_locator_history_delete_warning').closest('section');
    if (!dialog) {
        throw new Error('Expected dialog section to exist');
    }

    await user.click(within(dialog).getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(resourceListStore.deleteList).not.toBeCalled();
});

test('Delete if confirmation dialog is confirmed', async() => {
    const user = userEvent.setup();
    render(
        <ResourceLocatorHistory
            options={{webspace: 'sulu'}}
            resourceKey="history_routes"
        />
    );

    await user.click(screen.getByRole('button', {name: /sulu_admin\.show_history/}));

    const resourceListStore = resourceListStoreMock.mock.instances[0];
    resourceListStore.loading = false;
    resourceListStore.data = [
        {
            id: 3,
            slug: 'sulu.io/test',
            created: '2019-04-10T13:06:16',
        },
    ];

    const deleteListPromise = Promise.resolve();
    resourceListStore.deleteList.mockReturnValue(deleteListPromise);

    expect(await screen.findByText('sulu.io/test')).toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));

    const dialog = screen.getByText('sulu_admin.resource_locator_history_delete_warning').closest('section');
    if (!dialog) {
        throw new Error('Expected dialog section to exist');
    }

    await user.click(within(dialog).getByRole('button', {name: 'sulu_admin.ok'}));

    expect(resourceListStore.deleteList).toBeCalledWith([3]);

    await deleteListPromise;
});
