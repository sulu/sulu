/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {
    findAllElementsByType,
    findWithHighOrderFunction,
    renderWithRef,
} from 'sulu-admin-bundle/utils/TestHelper';

jest.useFakeTimers();

jest.mock('copy-to-clipboard', () => jest.fn());

jest.mock('sulu-admin-bundle/containers', () => ({
    withToolbar: jest.fn((Component) => Component),
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

test('Render a loading MediaFormats view', () => {
    const MediaFormats = require('../MediaFormats').default;
    const formatStore = require('../../../stores/formatStore');
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    formatStore.loadFormats.mockReturnValue(new Promise(() => undefined));
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.loading = true;

    const {container} = renderWithRef(
        <MediaFormats resourceStore={resourceStore} router={router} title="Test 1" />
    );

    expect(container).toMatchSnapshot();
});

test('Render a loading MediaFormats view if formats have not been loaded yet', () => {
    const MediaFormats = require('../MediaFormats').default;
    const formatStore = require('../../../stores/formatStore');
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    formatStore.loadFormats.mockReturnValue(new Promise(() => undefined));
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.loading = false;

    const {container} = renderWithRef(
        <MediaFormats resourceStore={resourceStore} router={router} />
    );

    expect(container).toMatchSnapshot();
});

test('Render a MediaFormats view', () => {
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
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.data.thumbnails = {
        '400x400': '/media/400x400/image.jpg',
        '800x800': '/media/800x800/image.jpg',
    };

    const {container} = renderWithRef(<MediaFormats resourceStore={resourceStore} router={router} title="Test 2" />);

    return formatPromise.then(() => {
        expect(container).toMatchSnapshot();
    });
});

test('Open the image in the given format when icon is clicked', () => {
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
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.data.thumbnails = {
        '400x400': '/media/400x400/image.jpg?v=1',
        '800x800': '/media/800x800/image.jpg?v=1',
    };

    const {instance: mediaFormats} = renderWithRef(<MediaFormats resourceStore={resourceStore} router={router} />);

    return formatPromise.then(() => {
        const rows = findAllElementsByType(mediaFormats.render(), 'Row');

        rows[0].props.buttons[0].onClick('400x400');
        expect(window.open).toHaveBeenLastCalledWith('/media/400x400/image.jpg?v=1&inline=1');
        rows[1].props.buttons[0].onClick('800x800');
        expect(window.open).toHaveBeenLastCalledWith('/media/800x800/image.jpg?v=1&inline=1');
    });
});

test('Copy the image URL for the given format when icon is clicked and show a success message', () => {
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
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.data.thumbnails = {
        '400x400': '/media/400x400/image.jpg?v=1',
        '800x800': '/media/800x800/image.jpg?v=1',
    };

    const {instance: mediaFormats} = renderWithRef(<MediaFormats resourceStore={resourceStore} router={router} />);

    return formatPromise.then(() => {
        const getRows = () => findAllElementsByType(mediaFormats.render(), 'Row');
        const getButtons = (index) => getRows()[index].props.buttons;

        getButtons(0)[1].onClick('400x400');
        expect(copyToClipboard).toHaveBeenLastCalledWith('http://localhost/media/400x400/image.jpg?v=1');
        expect(getButtons(0)[1].icon).toEqual('su-check');
        jest.runAllTimers();
        expect(getButtons(0)[1].icon).toEqual('su-copy');

        getButtons(1)[1].onClick('800x800');
        expect(copyToClipboard).toHaveBeenLastCalledWith('http://localhost/media/800x800/image.jpg?v=1');
        expect(getButtons(1)[1].icon).toEqual('su-check');
        jest.runAllTimers();
        expect(getButtons(0)[1].icon).toEqual('su-copy');
    });
});

test('Should change locale via locale chooser', () => {
    const formatStore = require('../../../stores/formatStore');
    formatStore.loadFormats.mockReturnValue(Promise.resolve());

    const MediaFormats = require('../MediaFormats').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaFormats);
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});

    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            name: 'sulu_media.media_formats',
            options: {
                locales: [],
            },
        },
    };
    const {instance: mediaFormats} = renderWithRef(<MediaFormats resourceStore={resourceStore} router={router} />);
    resourceStore.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaFormats);
    toolbarConfig.locale.onChange('en');
    expect(router.navigate).toHaveBeenCalledWith('sulu_media.media_formats', {locale: 'en'});
});

test('Should show locales from router options in toolbar', () => {
    const formatStore = require('../../../stores/formatStore');
    formatStore.loadFormats.mockReturnValue(Promise.resolve());

    const MediaFormats = require('../MediaFormats').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaFormats);
    const resourceStore = new ResourceStore('media', 1, {locale: observable.box()});

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                locales: ['en', 'de'],
            },
        },
    };
    const {instance: mediaFormats} = renderWithRef(<MediaFormats resourceStore={resourceStore} router={router} />);

    const toolbarConfig = toolbarFunction.call(mediaFormats);
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should navigate to defined route on back button click', () => {
    const formatStore = require('../../../stores/formatStore');
    formatStore.loadFormats.mockReturnValue(Promise.resolve());

    const MediaFormats = require('../MediaFormats').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaFormats);
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box('de')});

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
    const {instance: mediaFormats} = renderWithRef(<MediaFormats resourceStore={resourceStore} router={router} />);

    const toolbarConfig = toolbarFunction.call(mediaFormats);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toHaveBeenCalledWith('sulu_media.overview', {locale: 'de'});
});
