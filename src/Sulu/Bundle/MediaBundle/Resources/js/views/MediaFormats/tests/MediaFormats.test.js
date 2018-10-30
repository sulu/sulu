/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {mount, render} from 'enzyme';
import {findWithHighOrderFunction} from 'sulu-admin-bundle/utils/TestHelper';

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
        this.setLocale = jest.fn();
    }),
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: (key) => key,
}));

jest.mock('../../../stores/FormatStore', () => ({
    loadFormats: jest.fn(),
}));

beforeEach(() => {
    jest.resetModules();
});

test('Render a loading MediaFormats view', () => {
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
    resourceStore.loading = true;

    expect(render(
        <MediaFormats resourceStore={resourceStore} router={router} />
    )).toMatchSnapshot();
});

test('Render a loading MediaFormats view if formats have not been loaded yet', () => {
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
    resourceStore.loading = false;

    expect(render(
        <MediaFormats resourceStore={resourceStore} router={router} />
    )).toMatchSnapshot();
});

test('Render a MediaFormats view', () => {
    const formatStore = require('../../../stores/FormatStore');
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

    const mediaFormats = mount(<MediaFormats resourceStore={resourceStore} router={router} />);

    return formatPromise.then(() => {
        expect(mediaFormats.render()).toMatchSnapshot();
    });
});

test('Open the image in the given format when icon is clicked', () => {
    const formatStore = require('../../../stores/FormatStore');
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

    const mediaFormats = mount(<MediaFormats resourceStore={resourceStore} router={router} />);

    return formatPromise.then(() => {
        mediaFormats.update();

        mediaFormats.find('Row').at(0).find('ButtonCell').at(0).prop('onClick')('400x400');
        expect(window.open).toHaveBeenLastCalledWith('/media/400x400/image.jpg?v=1&inline=1');
        mediaFormats.find('Row').at(1).find('ButtonCell').at(0).prop('onClick')('800x800');
        expect(window.open).toHaveBeenLastCalledWith('/media/800x800/image.jpg?v=1&inline=1');
    });
});

test('Copy the image URL for the given format when icon is clicked and show a success message', () => {
    const formatStore = require('../../../stores/FormatStore');
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

    const mediaFormats = mount(<MediaFormats resourceStore={resourceStore} router={router} />);

    return formatPromise.then(() => {
        mediaFormats.update();

        mediaFormats.find('Row').at(0).find('ButtonCell').at(1).prop('onClick')('400x400');
        expect(copyToClipboard).toHaveBeenLastCalledWith('http://localhost/media/400x400/image.jpg?v=1');
        mediaFormats.update();
        expect(mediaFormats.find('Row').at(0).find('ButtonCell').at(1).prop('icon')).toEqual('su-check');
        jest.runAllTimers();
        mediaFormats.update();
        expect(mediaFormats.find('Row').at(0).find('ButtonCell').at(1).prop('icon')).toEqual('su-copy');

        mediaFormats.find('Row').at(1).find('ButtonCell').at(1).prop('onClick')('800x800');
        expect(copyToClipboard).toHaveBeenLastCalledWith('http://localhost/media/800x800/image.jpg?v=1');
        mediaFormats.update();
        expect(mediaFormats.find('Row').at(1).find('ButtonCell').at(1).prop('icon')).toEqual('su-check');
        jest.runAllTimers();
        mediaFormats.update();
        expect(mediaFormats.find('Row').at(0).find('ButtonCell').at(1).prop('icon')).toEqual('su-copy');
    });
});

test('Should change locale via locale chooser', () => {
    const formatStore = require('../../../stores/FormatStore');
    formatStore.loadFormats.mockReturnValue(Promise.resolve());

    const MediaFormats = require('../MediaFormats').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaFormats);
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
    const mediaFormats = mount(<MediaFormats resourceStore={resourceStore} router={router} />).get(0);
    resourceStore.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaFormats);
    toolbarConfig.locale.onChange('en');
    expect(resourceStore.setLocale).toBeCalledWith('en');
});

test('Should show locales from router options in toolbar', () => {
    const formatStore = require('../../../stores/FormatStore');
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
    const mediaFormats = mount(<MediaFormats resourceStore={resourceStore} router={router} />).get(0);

    const toolbarConfig = toolbarFunction.call(mediaFormats);
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should navigate to defined route on back button click', () => {
    const formatStore = require('../../../stores/FormatStore');
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
    const mediaFormats = mount(<MediaFormats resourceStore={resourceStore} router={router} />).get(0);

    const toolbarConfig = toolbarFunction.call(mediaFormats);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('sulu_media.overview', {locale: 'de'});
});
