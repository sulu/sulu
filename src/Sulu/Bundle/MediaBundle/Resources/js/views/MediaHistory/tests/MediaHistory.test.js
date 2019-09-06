/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {mount, render} from 'enzyme';
import {findWithHighOrderFunction} from 'sulu-admin-bundle/utils/TestHelper';

jest.mock('sulu-admin-bundle/containers', () => ({
    withToolbar: jest.fn((Component) => Component),
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

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: (key) => key,
}));

beforeEach(() => {
    jest.resetModules();
});

test('Render a loading MediaHistory view', () => {
    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.loading = true;

    expect(render(
        <MediaHistory resourceStore={resourceStore} router={router} title="Test 1" />
    )).toMatchSnapshot();
});

test('Render a MediaHistory view', () => {
    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
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

    expect(render(
        <MediaHistory resourceStore={resourceStore} router={router} title="Test 2" />
    )).toMatchSnapshot();
});

test('Open the old media when icon is clicked', () => {
    window.open = jest.fn();

    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
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

    const mediaHistory = mount(<MediaHistory resourceStore={resourceStore} router={router} />);

    mediaHistory.find('Row').at(0).find('ButtonCell').at(0).prop('onClick')(1);
    expect(window.open).toHaveBeenLastCalledWith('/media/1?v=1&inline=1');
    mediaHistory.find('Row').at(1).find('ButtonCell').at(0).prop('onClick')(2);
    expect(window.open).toHaveBeenLastCalledWith('/media/1?v=2&inline=1');
});

test('Deleting version should not happen when cancelled', () => {
    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
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

    const mediaHistory = mount(<MediaHistory resourceStore={resourceStore} router={router} />);

    mediaHistory.find('Row').at(1).find('ButtonCell').at(1).prop('onClick')(1);

    mediaHistory.update();

    expect(mediaHistory.find('Dialog').prop('open')).toEqual(true);
    mediaHistory.find('Dialog').prop('onCancel')();

    mediaHistory.update();
    expect(mediaHistory.find('Dialog').prop('open')).toEqual(false);
});

test('Deleting version should happen when confirmed', () => {
    const MediaHistory = require('../MediaHistory').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const ResourceRequester = require('sulu-admin-bundle/services').ResourceRequester;

    const deletePromise = Promise.resolve({});
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const locale = observable.box('de');

    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
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

    const mediaHistory = mount(<MediaHistory resourceStore={resourceStore} router={router} />);

    mediaHistory.find('Row').at(1).find('ButtonCell').at(1).prop('onClick')(1);

    mediaHistory.update();

    expect(mediaHistory.find('Dialog').prop('open')).toEqual(true);
    mediaHistory.find('Dialog').prop('onConfirm')();

    expect(ResourceRequester.delete).toBeCalledWith('media_versions', {id: 1, locale, version: 1});

    return deletePromise.then(() => {
        mediaHistory.update();
        expect(mediaHistory.find('Dialog').prop('open')).toEqual(false);
        expect(resourceStore.reload).toBeCalledWith();
    });
});

test('Should change locale via locale chooser', () => {
    const MediaHistory = require('../MediaHistory').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaHistory);
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});

    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            name: 'sulu_media.media_history',
            options: {
                locales: [],
            },
        },
    };
    const mediaHistory = mount(<MediaHistory resourceStore={resourceStore} router={router} />).get(0);
    resourceStore.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaHistory);
    toolbarConfig.locale.onChange('en');
    expect(router.navigate).toBeCalledWith('sulu_media.media_history', {locale: 'en'});
});

test('Should show locales from router options in toolbar', () => {
    const MediaHistory = require('../MediaHistory').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaHistory);
    const resourceStore = new ResourceStore('media', 1, {locale: observable.box()});

    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['en', 'de'],
            },
        },
    };
    const mediaHistory = mount(<MediaHistory resourceStore={resourceStore} router={router} />).get(0);

    const toolbarConfig = toolbarFunction.call(mediaHistory);
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should navigate to defined route on back button click', () => {
    const MediaHistory = require('../MediaHistory').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaHistory);
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
    const mediaHistory = mount(<MediaHistory resourceStore={resourceStore} router={router} />).get(0);

    const toolbarConfig = toolbarFunction.call(mediaHistory);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('sulu_media.overview', {locale: 'de'});
});
