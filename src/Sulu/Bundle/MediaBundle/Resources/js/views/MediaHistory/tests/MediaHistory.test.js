/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {act, render, screen, waitFor, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';

jest.mock('sulu-admin-bundle/containers/Toolbar/stores/toolbarStorePool', () => ({
    __esModule: true,
    DEFAULT_STORE_KEY: 'default',
    default: {
        setToolbarConfig: jest.fn(),
    },
}));

jest.mock('sulu-admin-bundle/containers', () => ({
    withToolbar: require('sulu-admin-bundle/containers/Toolbar/withToolbar').default,
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id, observableOptions = {}) {
        this.id = id;
        this.locale = observableOptions.locale;
        this.data = {
            versions: {},
        };
        this.reload = jest.fn();
    }),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    delete: jest.fn(),
}));

jest.mock('sulu-admin-bundle/utils/Translator');

beforeEach(() => {
    jest.resetModules();
});

function createRouter(options = {}) {
    return {
        attributes: {},
        bind: jest.fn(),
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
        navigate: jest.fn(),
        restore: jest.fn(),
        route: {
            name: 'sulu_media.media_history',
            options: {
                locales: [],
            },
        },
        ...options,
    };
}

function getVersionRow(version) {
    const row = screen.getByText('sulu_media.version ' + version).closest('tr');

    if (!row) {
        throw new Error('Expected version row');
    }

    return row;
}

function getVersionButton(version, icon) {
    return within(getVersionRow(version)).getByRole('button', {name: icon});
}

function queryDeleteDialog() {
    const title = screen.queryByText('sulu_admin.delete_warning_title');

    return title && title.closest('.dialogContainer');
}

function getDeleteDialog() {
    const dialog = queryDeleteDialog();

    if (!dialog) {
        throw new Error('Expected delete dialog');
    }

    return dialog;
}

function expectDeleteDialogClosed() {
    const dialog = queryDeleteDialog();

    if (!dialog) {
        expect(dialog).toBeNull();
        return;
    }

    expect(dialog).not.toHaveClass('open');
}

function getToolbarConfig() {
    const toolbarStorePool = require('sulu-admin-bundle/containers/Toolbar/stores/toolbarStorePool').default;
    const calls = toolbarStorePool.setToolbarConfig.mock.calls;

    return calls[calls.length - 1][1];
}

test('Render a loading MediaHistory view', () => {
    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const router = createRouter();
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.loading = true;

    const {container} = render(
        <MediaHistory resourceStore={resourceStore} router={router} title="Test 1" />
    );

    expect(container.innerHTML).toMatchSnapshot();
    expect(screen.getByText((content, element) => !!element && element.classList.contains('spinner')))
        .toBeInTheDocument();
});

test('Render a MediaHistory view', () => {
    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const router = createRouter();
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.data.versions = {
        1: {
            created: '2018-10-23T10:18',
            version: 1,
        },
        2: {
            created: '2018-10-23T10:25',
            version: 2,
        },
    };

    const {container} = render(
        <MediaHistory resourceStore={resourceStore} router={router} title="Test 2" />
    );

    expect(container.innerHTML).toMatchSnapshot();
});

test('Open the old media when icon is clicked', async() => {
    window.open = jest.fn();

    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const user = userEvent.setup();
    const router = createRouter();
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.data.versions = {
        1: {
            created: '2018-10-23T10:18',
            url: '/media/1?v=1',
            version: 1,
        },
        2: {
            created: '2018-10-23T10:25',
            url: '/media/1?v=2',
            version: 2,
        },
    };

    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    await user.click(getVersionButton(1, 'su-eye'));
    expect(window.open).toHaveBeenLastCalledWith('/media/1?v=1&inline=1');
    await user.click(getVersionButton(2, 'su-eye'));
    expect(window.open).toHaveBeenLastCalledWith('/media/1?v=2&inline=1');
});

test('Deleting version should not happen when cancelled', async() => {
    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const ResourceRequester = require('sulu-admin-bundle/services').ResourceRequester;
    const user = userEvent.setup();
    const router = createRouter();
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.data.version = 2;
    resourceStore.data.versions = {
        1: {
            created: '2018-10-23T10:18',
            url: '/media/1?v=1',
            version: 1,
        },
        2: {
            created: '2018-10-23T10:25',
            url: '/media/1?v=2',
            version: 2,
        },
    };

    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    await user.click(getVersionButton(1, 'su-trash-alt'));

    expect(getDeleteDialog()).toHaveClass('open');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

    expectDeleteDialogClosed();
    expect(ResourceRequester.delete).not.toHaveBeenCalled();
});

test('Deleting version should happen when confirmed', async() => {
    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const ResourceRequester = require('sulu-admin-bundle/services').ResourceRequester;
    const user = userEvent.setup();

    const deletePromise = Promise.resolve({});
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const locale = observable.box('de');

    const router = createRouter();
    const resourceStore = new ResourceStore('media', 1, {locale});
    resourceStore.data.version = 2;
    resourceStore.data.versions = {
        1: {
            created: '2018-10-23T10:18',
            url: '/media/1?v=1',
            version: 1,
        },
        2: {
            created: '2018-10-23T10:25',
            url: '/media/1?v=2',
            version: 2,
        },
    };

    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    await user.click(getVersionButton(1, 'su-trash-alt'));

    expect(getDeleteDialog()).toHaveClass('open');
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(ResourceRequester.delete).toHaveBeenCalledWith('media_versions', {id: 1, locale, version: 1});

    await deletePromise;
    await waitFor(() => expectDeleteDialogClosed());
    expect(resourceStore.reload).toHaveBeenCalledWith();
});

test('Deleting version should be disabled on latest version', () => {
    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const ResourceRequester = require('sulu-admin-bundle/services').ResourceRequester;

    const deletePromise = Promise.resolve({});
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const locale = observable.box('de');

    const router = createRouter();
    const resourceStore = new ResourceStore('media', 1, {locale});
    resourceStore.data.version = 2;
    resourceStore.data.versions = {
        1: {
            created: '2018-10-23T10:18',
            url: '/media/1?v=1',
            version: 1,
        },
        2: {
            created: '2018-10-23T10:25',
            url: '/media/1?v=2',
            version: 2,
        },
    };

    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    expect(getVersionButton(2, 'su-lock')).toBeDisabled();
    expect(getVersionButton(1, 'su-trash-alt')).toBeEnabled();
});

test('Should change locale via locale chooser', () => {
    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});

    const router = createRouter();
    render(<MediaHistory resourceStore={resourceStore} router={router} />);
    act(() => resourceStore.locale.set('de'));

    const toolbarConfig = getToolbarConfig();
    toolbarConfig.locale.onChange('en');
    expect(router.navigate).toHaveBeenCalledWith('sulu_media.media_history', {locale: 'en'});
});

test('Should show locales from router options in toolbar', () => {
    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', 1, {locale: observable.box()});

    const router = createRouter({
        route: {
            name: 'sulu_media.media_history',
            options: {
                locales: ['en', 'de'],
            },
        },
    });
    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    const toolbarConfig = getToolbarConfig();
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should navigate to defined route on back button click', () => {
    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box('de')});

    const router = createRouter();
    render(<MediaHistory resourceStore={resourceStore} router={router} />);

    const toolbarConfig = getToolbarConfig();
    toolbarConfig.backButton.onClick();
    expect(router.restore).toHaveBeenCalledWith('sulu_media.overview', {locale: 'de'});
});
