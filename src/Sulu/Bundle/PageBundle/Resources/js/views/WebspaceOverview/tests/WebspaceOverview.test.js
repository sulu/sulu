// @flow
import React from 'react';
import {mount} from 'enzyme';
import {Requester, Router} from 'sulu-admin-bundle/services';
import {findWithHighOrderFunction} from 'sulu-admin-bundle/utils/TestHelper';
import WebspaceStore from '../../../stores/WebspaceStore';

jest.mock('sulu-admin-bundle/containers', () => ({
    withToolbar: jest.fn((Component) => Component),
    List: require('sulu-admin-bundle/containers/List/List').default,
    ListStore: class {
        static getActiveSetting = jest.fn();

        constructor(resourceKey, listKey, userSettingsKey, observableOptions) {
            this.resourceKey = resourceKey;
            this.observableOptions = observableOptions;
        }

        resourceKey;
        observableOptions;
        activeItems = [];
        active = {
            get: jest.fn(),
            set: jest.fn(),
        };
        sortColumn = {
            get: jest.fn(),
        };
        sortOrder = {
            get: jest.fn(),
        };
        limit = {
            get: jest.fn().mockReturnValue(10),
        };
        setLimit = jest.fn();
        selections = [];
        selectionIds = [];
        getPage = jest.fn().mockReturnValue(1);
        destroy = jest.fn();
        sendRequest = jest.fn();
        updateLoadingStrategy = jest.fn();
        updateStructureStrategy = jest.fn();
        clear = jest.fn();
    },
    FlatStructureStrategy: require(
        'sulu-admin-bundle/containers/List/structureStrategies/FlatStructureStrategy'
    ).default,
    FullLoadingStrategy: require(
        'sulu-admin-bundle/containers/List/loadingStrategies/FullLoadingStrategy'
    ).default,
}));

jest.mock('sulu-admin-bundle/containers/List/registries/ListAdapterRegistry', () => ({
    get: jest.fn().mockReturnValue(require('sulu-admin-bundle/containers/List/adapters/ColumnListAdapter').default),
    has: jest.fn().mockReturnValue(true),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    userStore: {
        setPersistentSetting: jest.fn(),
        getPersistentSetting: jest.fn(),
    },
}));

jest.mock('../../../stores/WebspaceStore', () => ({
    loadWebspaces: jest.fn(() => Promise.resolve()),
}));

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    delete: jest.fn(),
}));

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.bind = jest.fn();
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.selections = [];
}));
jest.mock('sulu-admin-bundle/containers/ListOverlay', () => jest.fn().mockReturnValue(null));

beforeEach(() => {
    jest.resetModules();
});

test('Render WebspaceOverview', () => {
    // $FlowFixMe
    const webspaceStore: typeof WebspaceStore = require('../../../stores/WebspaceStore');
    const promise = Promise.resolve(
        [
            {key: 'sulu', localizations: [{locale: 'en', default: true}]},
            {key: 'sulu_blog', localizations: [{locale: 'en', default: false}, {locale: 'de', default: true}]},
        ]
    );
    webspaceStore.loadWebspaces.mockReturnValue(promise);

    const WebspaceOverview = require('../WebspaceOverview').default;
    const router = new Router({});

    const webspaceOverview = mount(<WebspaceOverview route={router.route} router={router} />);
    webspaceOverview.instance().listStore.data = [
        [
            {id: 1},
        ],
    ];

    return promise.then(() => {
        webspaceOverview.update();
        expect(webspaceOverview.render()).toMatchSnapshot();
    });
});

test('Should change webspace when value of webspace select is changed', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const WebspaceOverview = require('../WebspaceOverview').default;
    // $FlowFixMe
    const toolbarFunction = findWithHighOrderFunction(withToolbar, WebspaceOverview);
    // $FlowFixMe
    const webspaceStore: typeof WebspaceStore = require('../../../stores/WebspaceStore');
    const userStore = require('sulu-admin-bundle/stores').userStore;

    const promise = Promise.resolve(
        [
            {
                key: 'sulu',
                localizations: [{locale: 'en', default: true}],
                allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
            },
            {
                key: 'sulu_blog',
                localizations: [{locale: 'en', default: false}, {locale: 'de', default: true}],
                allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
            },
        ]
    );

    webspaceStore.loadWebspaces.mockReturnValue(promise);

    const router = new Router({});

    const webspaceOverview = mount(<WebspaceOverview route={router.route} router={router} />);

    return promise.then(() => {
        webspaceOverview.update();
        webspaceOverview.instance().webspace.set('sulu');
        webspaceOverview.instance().locale.set('en');
        expect(webspaceOverview.instance().webspace.get()).toBe('sulu');
        expect(webspaceOverview.instance().locale.get()).toBe('en');

        const toolbarConfig = toolbarFunction.call(webspaceOverview.instance());
        expect(toolbarConfig.locale.value).toBe('en');
        expect(toolbarConfig.locale.options).toEqual(
            expect.arrayContaining(
                [
                    expect.objectContaining({label: 'en', value: 'en'}),
                    expect.objectContaining({label: 'de', value: 'de'}),
                ]
            )
        );

        webspaceOverview.update();
        webspaceOverview.find('WebspaceSelect').prop('onChange')('sulu_blog');
        expect(webspaceOverview.instance().listStore.destroy).toBeCalledWith();
        expect(webspaceOverview.instance().listStore.active.set).toBeCalledWith(undefined);
        expect(webspaceOverview.instance().webspace.get()).toBe('sulu_blog');
        expect(webspaceOverview.instance().locale.get()).toBe('de');
        expect(userStore.setPersistentSetting).lastCalledWith('sulu_page.webspace_overview.webspace', 'sulu_blog');

        const toolbarConfigNew = toolbarFunction.call(webspaceOverview.instance());
        expect(toolbarConfigNew.locale.value).toBe('de');
        expect(toolbarConfigNew.locale.options).toEqual(
            expect.arrayContaining(
                [
                    expect.objectContaining({label: 'de', value: 'de'}),
                ]
            )
        );
    });
});

test('Should change excludeGhostsAndShadows when value of toggler is changed', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const WebspaceOverview = require('../WebspaceOverview').default;
    // $FlowFixMe
    const toolbarFunction = findWithHighOrderFunction(withToolbar, WebspaceOverview);
    // $FlowFixMe
    const webspaceStore: typeof WebspaceStore = require('../../../stores/WebspaceStore');

    const promise = Promise.resolve(
        [
            {
                key: 'sulu',
                localizations: [{locale: 'en', default: true}],
                allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
            },
        ]
    );

    webspaceStore.loadWebspaces.mockReturnValue(promise);

    const router = new Router({});

    const webspaceOverview = mount(<WebspaceOverview route={router.route} router={router} />);

    return promise.then(() => {
        webspaceOverview.instance().webspace.set('sulu');
        webspaceOverview.update();

        const excludeGhostsAndShadows = webspaceOverview.instance().excludeGhostsAndShadows;
        expect(excludeGhostsAndShadows.get()).toEqual(false);
        expect(webspaceOverview.instance().listStore.observableOptions).toEqual(expect.objectContaining({
            'exclude-ghosts': excludeGhostsAndShadows,
            'exclude-shadows': excludeGhostsAndShadows,
        }));

        let toolbarConfig = toolbarFunction.call(webspaceOverview.instance());
        expect(toolbarConfig.items[0].value).toEqual(true);

        toolbarConfig.items[0].onClick();
        toolbarConfig = toolbarFunction.call(webspaceOverview.instance());
        expect(toolbarConfig.items[0].value).toEqual(false);
        expect(webspaceOverview.instance().listStore.clear).toBeCalledWith();
        expect(webspaceOverview.instance().excludeGhostsAndShadows.get()).toEqual(true);

        toolbarConfig.items[0].onClick();
        toolbarConfig = toolbarFunction.call(webspaceOverview.instance());
        expect(toolbarConfig.items[0].value).toEqual(true);
        expect(webspaceOverview.instance().excludeGhostsAndShadows.get()).toEqual(false);
    });
});

test('Should close Cache Clear dialog if cancel is clicked', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const WebspaceOverview = require('../WebspaceOverview').default;
    // $FlowFixMe
    const toolbarFunction = findWithHighOrderFunction(withToolbar, WebspaceOverview);
    // $FlowFixMe
    const webspaceStore: typeof WebspaceStore = require('../../../stores/WebspaceStore');

    const promise = Promise.resolve(
        [
            {
                key: 'sulu',
                localizations: [{locale: 'en', default: true}],
                allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
            },
        ]
    );

    webspaceStore.loadWebspaces.mockReturnValue(promise);

    const router = new Router({});

    const webspaceOverview = mount(<WebspaceOverview route={router.route} router={router} />);

    return promise.then(() => {
        webspaceOverview.instance().webspace.set('sulu');
        const toolbarConfig = toolbarFunction.call(webspaceOverview.instance());

        expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('open'))
            .toEqual(false);
        expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('confirmLoading'))
            .toEqual(false);
        toolbarConfig.items[1].onClick();

        webspaceOverview.update();
        expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('open'))
            .toEqual(true);
        expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('confirmLoading'))
            .toEqual(false);

        webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('onCancel')();
        webspaceOverview.update();
        expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('open'))
            .toEqual(false);
        expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('confirmLoading'))
            .toEqual(false);

        expect(Requester.delete).not.toBeCalled();
    });
});

test('Should clear cache and close dialog if confirm is clicked', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const Requester = require('sulu-admin-bundle/services').Requester;
    const WebspaceOverview = require('../WebspaceOverview').default;
    // $FlowFixMe
    const toolbarFunction = findWithHighOrderFunction(withToolbar, WebspaceOverview);
    // $FlowFixMe
    const webspaceStore: typeof WebspaceStore = require('../../../stores/WebspaceStore');

    const promise = Promise.resolve(
        [
            {
                key: 'sulu',
                localizations: [{locale: 'en', default: true}],
                allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
            },
        ]
    );

    webspaceStore.loadWebspaces.mockReturnValue(promise);

    const cacheClearPromise = Promise.resolve();
    Requester.delete.mockReturnValue(cacheClearPromise);

    const router = new Router({});

    WebspaceOverview.clearCacheEndpoint = '/admin/website/cache';
    const webspaceOverview = mount(<WebspaceOverview route={router.route} router={router} />);

    return promise.then(() => {
        webspaceOverview.instance().webspace.set('sulu');
        const toolbarConfig = toolbarFunction.call(webspaceOverview.instance());

        expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('open'))
            .toEqual(false);
        expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('confirmLoading'))
            .toEqual(false);
        toolbarConfig.items[1].onClick();

        webspaceOverview.update();
        expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('open'))
            .toEqual(true);
        expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('confirmLoading'))
            .toEqual(false);

        webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('onConfirm')();
        expect(Requester.delete).toBeCalledWith('/admin/website/cache');

        webspaceOverview.update();
        expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('open'))
            .toEqual(true);
        expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('confirmLoading'))
            .toEqual(true);

        return cacheClearPromise.then(() => {
            webspaceOverview.update();
            expect(webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('open'))
                .toEqual(false);
            expect(
                webspaceOverview.find('Dialog[title="sulu_page.cache_clear_warning_title"]').prop('confirmLoading')
            ).toEqual(false);
        });
    });
});

test('Should load webspace and active route attribute from listStore and userStore', () => {
    const WebspaceOverview = require('../WebspaceOverview').default;
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const userStore = require('sulu-admin-bundle/stores').userStore;

    userStore.getPersistentSetting.mockImplementation((key) => {
        if (key === 'sulu_page.webspace_overview.webspace') {
            return 'sulu';
        }
    });

    ListStore.getActiveSetting.mockReturnValueOnce('some-uuid');

    // $FlowFixMe
    expect(WebspaceOverview.getDerivedRouteAttributes(undefined, {webspace: 'abc'})).toEqual({
        active: 'some-uuid',
        webspace: 'abc',
    });
});

test('Should bind router', () => {
    const WebspaceOverview = require('../WebspaceOverview').default;
    const router = new Router({});

    const webspaceOverview = mount(<WebspaceOverview route={router.route} router={router} />);
    webspaceOverview.instance().webspace.set('sulu');
    const page = webspaceOverview.instance().page;
    const locale = webspaceOverview.instance().locale;
    const webspace = webspaceOverview.instance().webspace;
    const excludeGhostsAndShadows = webspaceOverview.instance().excludeGhostsAndShadows;

    expect(router.bind).toBeCalledWith('page', page, 1);
    expect(router.bind).toBeCalledWith('excludeGhostsAndShadows', excludeGhostsAndShadows, false);
    expect(router.bind).toBeCalledWith('locale', locale);
    expect(router.bind).toBeCalledWith('webspace', webspace);
    expect(router.bind).toBeCalledWith('active', webspaceOverview.instance().listStore.active);
});

test('Should call disposers on unmount', () => {
    const WebspaceOverview = require('../WebspaceOverview').default;
    const router = new Router({});

    const webspaceOverview = mount(<WebspaceOverview route={router.route} router={router} />);

    const listStore = webspaceOverview.instance().listStore;

    const excludeGhostsAndShadowsDisposerSpy = jest.fn();
    const webspaceDisposerSpy = jest.fn();
    webspaceOverview.instance().excludeGhostsAndShadowsDisposer = excludeGhostsAndShadowsDisposerSpy;
    webspaceOverview.instance().webspaceDisposer = webspaceDisposerSpy;
    webspaceOverview.unmount();

    expect(listStore.destroy).toBeCalledWith();
    expect(excludeGhostsAndShadowsDisposerSpy).toBeCalledWith();
    expect(webspaceDisposerSpy).toBeCalledWith();
});
