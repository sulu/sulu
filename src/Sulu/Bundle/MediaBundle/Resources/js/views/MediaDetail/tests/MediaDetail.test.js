/* eslint-disable flowtype/require-valid-file-annotation */
import 'url-search-params-polyfill';
import React from 'react';
import {mount, render} from 'enzyme';

jest.mock('sulu-admin-bundle/containers', () => ({
    withToolbar: jest.fn((Component) => Component),
}));

jest.mock('sulu-admin-bundle/services', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.save':
                return 'Save';
        }
    },
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
    put: jest.fn(),
}));

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.update = jest.fn();
}));

beforeEach(() => {
    jest.resetModules();
});

test('Render a MediaDetail view', () => {
    const MediaDetail = require('../MediaDetail').default;
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
    const resourceStore = new ResourceStore('media', '1');

    expect(render(
        <MediaDetail router={router} resourceStore={resourceStore} />
    )).toMatchSnapshot();
});

test('Should change locale via locale chooser', () => {
    const MediaDetail = require('../MediaDetail').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('test', '1');

    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                backRoute: 'test_route',
                locales: [],
            },
        },
    };
    const mediaDetail = mount(<MediaDetail router={router} resourceStore={resourceStore} />).get(0);
    resourceStore.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaDetail);
    toolbarConfig.locale.onChange('en');
    expect(resourceStore.locale.get()).toBe('en');
});

test('Should navigate to defined route on back button click', () => {
    const MediaDetail = require('../MediaDetail').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('test', '1');

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                backRoute: 'test_route',
                locales: [],
            },
        },
        attributes: {},
    };
    const mediaDetail = mount(<MediaDetail router={router} resourceStore={resourceStore} />).get(0);
    resourceStore.setLocale('de');

    const toolbarConfig = toolbarFunction.call(mediaDetail);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('test_route', {locale: 'de'});
});

test('Should show locales from router options in toolbar', () => {
    const MediaDetail = require('../MediaDetail').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('test', 1);

    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['en', 'de'],
            },
        },
        attributes: {},
    };
    const mediaDetail = mount(<MediaDetail router={router} resourceStore={resourceStore} />).get(0);

    const toolbarConfig = toolbarFunction.call(mediaDetail);
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should call update method of MediaUploadStore if a file was dropped', () => {
    const testId = 1;
    const testFile = {name: 'test.jpg'};
    const MediaDetail = require('../MediaDetail').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('test', testId);

    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {},
    };
    const mediaDetail = mount(<MediaDetail router={router} resourceStore={resourceStore} />).get(0);

    resourceStore.set('id', testId);
    mediaDetail.handleMediaDrop(testFile);
    expect(mediaDetail.mediaUploadStore.update).toHaveBeenCalledWith(testId, testFile);
});
