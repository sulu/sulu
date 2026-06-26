/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {
    findAllElementsByType,
    findElementByType,
    findWithHighOrderFunction,
    renderWithRef,
    waitForReaction,
} from 'sulu-admin-bundle/utils/TestHelper';

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

jest.mock('sulu-admin-bundle/utils/Translator');

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

    const {container} = renderWithRef(
        <MediaHistory resourceStore={resourceStore} router={router} title="Test 1" />
    );

    expect(container.innerHTML).toMatchSnapshot();
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

    const {container} = renderWithRef(
        <MediaHistory resourceStore={resourceStore} router={router} title="Test 2" />
    );

    expect(container.innerHTML).toMatchSnapshot();
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

    const {instance: mediaHistory} = renderWithRef(<MediaHistory resourceStore={resourceStore} router={router} />);
    const rows = findAllElementsByType(mediaHistory.render(), 'Row');

    rows[0].props.buttons[0].onClick(1);
    expect(window.open).toHaveBeenLastCalledWith('/media/1?v=1&inline=1');
    rows[1].props.buttons[0].onClick(2);
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

    const {instance: mediaHistory} = renderWithRef(<MediaHistory resourceStore={resourceStore} router={router} />);

    findAllElementsByType(mediaHistory.render(), 'Row')[1].props.buttons[1].onClick(1);

    expect(findElementByType(mediaHistory.render(), 'Dialog').props.open).toEqual(true);
    findElementByType(mediaHistory.render(), 'Dialog').props.onCancel();

    expect(findElementByType(mediaHistory.render(), 'Dialog').props.open).toEqual(false);
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

    const {instance: mediaHistory} = renderWithRef(<MediaHistory resourceStore={resourceStore} router={router} />);

    findAllElementsByType(mediaHistory.render(), 'Row')[1].props.buttons[1].onClick(1);

    expect(findElementByType(mediaHistory.render(), 'Dialog').props.open).toEqual(true);
    findElementByType(mediaHistory.render(), 'Dialog').props.onConfirm();

    expect(ResourceRequester.delete).toHaveBeenCalledWith('media_versions', {id: 1, locale, version: 1});

    return deletePromise.then(() => {
        return waitForReaction().then(() => {
            expect(findElementByType(mediaHistory.render(), 'Dialog').props.open).toEqual(false);
            expect(resourceStore.reload).toHaveBeenCalledWith();
        });
    });
});

test('Deleting version should be disabled on latest version', () => {
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

    const {instance: mediaHistory} = renderWithRef(<MediaHistory resourceStore={resourceStore} router={router} />);
    const rows = findAllElementsByType(mediaHistory.render(), 'Row');
    const latestVersionRow = rows.find((row) => row.props.id === 2);
    const oldVersionRow = rows.find((row) => row.props.id === 1);

    expect(latestVersionRow.props.buttons[1].disabled).toEqual(true);
    expect(oldVersionRow.props.buttons[1].disabled).not.toEqual(true);
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
    const {instance: mediaHistory} = renderWithRef(<MediaHistory resourceStore={resourceStore} router={router} />);
    resourceStore.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaHistory);
    toolbarConfig.locale.onChange('en');
    expect(router.navigate).toHaveBeenCalledWith('sulu_media.media_history', {locale: 'en'});
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
    const {instance: mediaHistory} = renderWithRef(<MediaHistory resourceStore={resourceStore} router={router} />);

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
    const {instance: mediaHistory} = renderWithRef(<MediaHistory resourceStore={resourceStore} router={router} />);

    const toolbarConfig = toolbarFunction.call(mediaHistory);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toHaveBeenCalledWith('sulu_media.overview', {locale: 'de'});
});
