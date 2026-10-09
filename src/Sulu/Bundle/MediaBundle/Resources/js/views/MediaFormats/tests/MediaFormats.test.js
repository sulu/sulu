/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {act, render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';

jest.useFakeTimers();

jest.mock('copy-to-clipboard', () => jest.fn());

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
        this.locale = observableOptions.locale;
        this.data = {
            thumbnails: {},
        };
    }),
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../stores/formatStore', () => ({
    loadFormats: jest.fn(),
}));

beforeEach(() => {
    jest.resetModules();
});

function createRouter(options = {}) {
    return {
        bind: jest.fn(),
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
        navigate: jest.fn(),
        restore: jest.fn(),
        route: {
            name: 'sulu_media.media_formats',
            options: {
                locales: [],
            },
        },
        ...options,
    };
}

function createUser() {
    return userEvent.setup({advanceTimers: jest.advanceTimersByTime});
}

function getFormatRow(formatKey) {
    const row = screen.getByText(formatKey).closest('tr');

    if (!row) {
        throw new Error('Expected format row');
    }

    return row;
}

function getFormatButton(formatKey, icon) {
    return within(getFormatRow(formatKey)).getByRole('button', {name: icon});
}

function expectLoader() {
    expect(screen.getByText((content, element) => !!element && element.classList.contains('spinner')))
        .toBeInTheDocument();
}

function getToolbarConfig() {
    const toolbarStorePool = require('sulu-admin-bundle/containers/Toolbar/stores/toolbarStorePool').default;
    const calls = toolbarStorePool.setToolbarConfig.mock.calls;

    return calls[calls.length - 1][1];
}

test('Render a loading MediaFormats view', () => {
    const MediaFormats = require('../MediaFormats').default;
    const formatStore = require('../../../stores/formatStore');
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    formatStore.loadFormats.mockReturnValue(new Promise(() => undefined));
    const router = createRouter();
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.loading = true;

    const {container} = render(
        <MediaFormats resourceStore={resourceStore} router={router} title="Test 1" />
    );

    expect(container).toMatchSnapshot();
    expectLoader();
});

test('Render a loading MediaFormats view if formats have not been loaded yet', () => {
    const MediaFormats = require('../MediaFormats').default;
    const formatStore = require('../../../stores/formatStore');
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    formatStore.loadFormats.mockReturnValue(new Promise(() => undefined));
    const router = createRouter();
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.loading = false;

    const {container} = render(
        <MediaFormats resourceStore={resourceStore} router={router} />
    );

    expect(container).toMatchSnapshot();
    expectLoader();
});

test('Render a MediaFormats view', async() => {
    const formatStore = require('../../../stores/formatStore');
    const formatPromise = Promise.resolve([
        {
            key: '400x400',
            title: 'Contact',
        },
        {
            key: '800x800',
            title: 'Account',
        },
    ]);
    formatStore.loadFormats.mockReturnValue(formatPromise);

    const MediaFormats = require('../MediaFormats').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const router = createRouter();
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.data.thumbnails = {
        '400x400': '/media/400x400/image.jpg',
        '800x800': '/media/800x800/image.jpg',
    };

    const {container} = render(<MediaFormats resourceStore={resourceStore} router={router} title="Test 2" />);

    await formatPromise;
    expect(await screen.findByText('Contact')).toBeInTheDocument();
    expect(screen.getByText('Account')).toBeInTheDocument();
    expect(container).toMatchSnapshot();
});

test('Open the image in the given format when icon is clicked', async() => {
    const formatStore = require('../../../stores/formatStore');
    const formatPromise = Promise.resolve([
        {
            key: '400x400',
        },
        {
            key: '800x800',
        },
    ]);
    formatStore.loadFormats.mockReturnValue(formatPromise);

    window.open = jest.fn();

    const MediaFormats = require('../MediaFormats').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const user = createUser();
    const router = createRouter();
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.data.thumbnails = {
        '400x400': '/media/400x400/image.jpg?v=1',
        '800x800': '/media/800x800/image.jpg?v=1',
    };

    render(<MediaFormats resourceStore={resourceStore} router={router} />);

    await formatPromise;
    expect(await screen.findByText('400x400')).toBeInTheDocument();

    await user.click(getFormatButton('400x400', 'su-eye'));
    expect(window.open).toHaveBeenLastCalledWith('/media/400x400/image.jpg?v=1&inline=1');
    await user.click(getFormatButton('800x800', 'su-eye'));
    expect(window.open).toHaveBeenLastCalledWith('/media/800x800/image.jpg?v=1&inline=1');
});

test('Copy the image URL for the given format when icon is clicked and show a success message', async() => {
    const formatStore = require('../../../stores/formatStore');
    const formatPromise = Promise.resolve([
        {
            key: '400x400',
        },
        {
            key: '800x800',
        },
    ]);
    formatStore.loadFormats.mockReturnValue(formatPromise);

    const copyToClipboard = require('copy-to-clipboard');
    const MediaFormats = require('../MediaFormats').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const user = createUser();
    const router = createRouter();
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.data.thumbnails = {
        '400x400': '/media/400x400/image.jpg?v=1',
        '800x800': '/media/800x800/image.jpg?v=1',
    };

    render(<MediaFormats resourceStore={resourceStore} router={router} />);

    await formatPromise;
    expect(await screen.findByText('400x400')).toBeInTheDocument();

    await user.click(getFormatButton('400x400', 'su-copy'));
    expect(copyToClipboard).toHaveBeenLastCalledWith('http://localhost/media/400x400/image.jpg?v=1');
    expect(getFormatButton('400x400', 'su-check')).toBeInTheDocument();
    jest.runAllTimers();
    expect(getFormatButton('400x400', 'su-copy')).toBeInTheDocument();

    await user.click(getFormatButton('800x800', 'su-copy'));
    expect(copyToClipboard).toHaveBeenLastCalledWith('http://localhost/media/800x800/image.jpg?v=1');
    expect(getFormatButton('800x800', 'su-check')).toBeInTheDocument();
    jest.runAllTimers();
    expect(getFormatButton('800x800', 'su-copy')).toBeInTheDocument();
});

test('Should change locale via locale chooser', () => {
    const formatStore = require('../../../stores/formatStore');
    formatStore.loadFormats.mockReturnValue(Promise.resolve());

    const MediaFormats = require('../MediaFormats').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});

    const router = createRouter();
    render(<MediaFormats resourceStore={resourceStore} router={router} />);
    act(() => resourceStore.locale.set('de'));

    const toolbarConfig = getToolbarConfig();
    toolbarConfig.locale.onChange('en');
    expect(router.navigate).toHaveBeenCalledWith('sulu_media.media_formats', {locale: 'en'});
});

test('Should show locales from router options in toolbar', () => {
    const formatStore = require('../../../stores/formatStore');
    formatStore.loadFormats.mockReturnValue(Promise.resolve());

    const MediaFormats = require('../MediaFormats').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', 1, {locale: observable.box()});

    const router = createRouter({
        route: {
            name: 'sulu_media.media_formats',
            options: {
                locales: ['en', 'de'],
            },
        },
    });
    render(<MediaFormats resourceStore={resourceStore} router={router} />);

    const toolbarConfig = getToolbarConfig();
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should navigate to defined route on back button click', () => {
    const formatStore = require('../../../stores/formatStore');
    formatStore.loadFormats.mockReturnValue(Promise.resolve());

    const MediaFormats = require('../MediaFormats').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box('de')});

    const router = createRouter();
    render(<MediaFormats resourceStore={resourceStore} router={router} />);

    const toolbarConfig = getToolbarConfig();
    toolbarConfig.backButton.onClick();
    expect(router.restore).toHaveBeenCalledWith('sulu_media.overview', {locale: 'de'});
});
