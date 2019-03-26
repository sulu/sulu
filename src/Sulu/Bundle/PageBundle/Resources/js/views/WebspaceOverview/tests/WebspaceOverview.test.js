// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount} from 'enzyme';
import {Requester, Router} from 'sulu-admin-bundle/services';
import {findWithHighOrderFunction} from 'sulu-admin-bundle/utils/TestHelper';

jest.mock('sulu-admin-bundle/containers', () => ({
    FlatStructureStrategy: require(
        'sulu-admin-bundle/containers/List/structureStrategies/FlatStructureStrategy'
    ).default,
    FullLoadingStrategy: require(
        'sulu-admin-bundle/containers/List/loadingStrategies/FullLoadingStrategy'
    ).default,
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
    withToolbar: jest.fn((Component) => Component),
}));

jest.mock('sulu-admin-bundle/containers/List/registries/ListAdapterRegistry', () => ({
    get: jest.fn().mockReturnValue(require('sulu-admin-bundle/containers/List/adapters/ColumnListAdapter').default),
    has: jest.fn().mockReturnValue(true),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/stores/UserStore', () => ({
    getPersistentSetting: jest.fn(),
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
    const webspaceKey = observable.box('sulu');
    // $FlowFixMe
    const webspace = {};

    const WebspaceOverview = require('../WebspaceOverview').default;
    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const webspaceOverview = mount(
        <WebspaceOverview route={router.route} router={router} webspace={webspace} webspaceKey={webspaceKey} />
    );

    webspaceOverview.update();
    expect(webspaceOverview.render()).toMatchSnapshot();
});

test('Should show the locales from the webspace configuration for the toolbar', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const WebspaceOverview = require('../WebspaceOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, WebspaceOverview);

    const webspaceKey = observable.box('sulu');

    // $FlowFixMe
    const webspace = {
        key: 'sulu',
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
    };

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const webspaceOverview = mount(
        <WebspaceOverview route={router.route} router={router} webspace={webspace} webspaceKey={webspaceKey} />
    );

    webspaceOverview.instance().locale.set('en');
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
});

test('Should change excludeGhostsAndShadows when value of toggler is changed', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const WebspaceOverview = require('../WebspaceOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, WebspaceOverview);

    const webspaceKey = observable.box('sulu');

    // $FlowFixMe
    const webspace = {
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
        key: 'sulu',
    };

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const webspaceOverview = mount(
        <WebspaceOverview route={router.route} router={router} webspace={webspace} webspaceKey={webspaceKey} />
    );

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

test('Should close Cache Clear dialog if cancel is clicked', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const WebspaceOverview = require('../WebspaceOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, WebspaceOverview);

    const webspaceKey = observable.box('sulu');

    // $FlowFixMe
    const webspace = {
        key: 'sulu',
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
    };

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const webspaceOverview = mount(
        <WebspaceOverview
            route={router.route}
            router={router}
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

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

test('Should clear cache and close dialog if confirm is clicked', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const Requester = require('sulu-admin-bundle/services').Requester;
    const WebspaceOverview = require('../WebspaceOverview').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, WebspaceOverview);

    const webspaceKey = observable.box('sulu');

    // $FlowFixMe
    const webspace = {
        key: 'sulu',
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
    };

    const cacheClearPromise = Promise.resolve();
    Requester.delete.mockReturnValue(cacheClearPromise);

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    WebspaceOverview.clearCacheEndpoint = '/admin/website/cache';
    const webspaceOverview = mount(
        <WebspaceOverview
            route={router.route}
            router={router}
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

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
    expect(WebspaceOverview.getDerivedRouteAttributes(undefined, {webspace: 'abc'})).toEqual({active: 'some-uuid'});
    expect(ListStore.getActiveSetting).toBeCalledWith('pages', 'webspace_overview_abc');
});

test('Should bind router', () => {
    const WebspaceOverview = require('../WebspaceOverview').default;

    const webspaceKey = observable.box('sulu');
    // $FlowFixMe
    const webspace = {};

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const webspaceOverview = mount(
        <WebspaceOverview route={router.route} router={router} webspace={webspace} webspaceKey={webspaceKey} />
    );
    const page = webspaceOverview.instance().page;
    const locale = webspaceOverview.instance().locale;
    const excludeGhostsAndShadows = webspaceOverview.instance().excludeGhostsAndShadows;

    expect(router.bind).toBeCalledWith('page', page, 1);
    expect(router.bind).toBeCalledWith('excludeGhostsAndShadows', excludeGhostsAndShadows, false);
    expect(router.bind).toBeCalledWith('locale', locale);
    expect(router.bind).toBeCalledWith('active', webspaceOverview.instance().listStore.active);
});

test('Should call disposers on unmount', () => {
    const WebspaceOverview = require('../WebspaceOverview').default;

    const webspaceKey = observable.box('sulu');
    // $FlowFixMe
    const webspace = {};

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const webspaceOverview = mount(
        <WebspaceOverview
            route={router.route}
            router={router}
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const listStore = webspaceOverview.instance().listStore;

    const excludeGhostsAndShadowsDisposerSpy = jest.fn();
    webspaceOverview.instance().excludeGhostsAndShadowsDisposer = excludeGhostsAndShadowsDisposerSpy;
    webspaceOverview.unmount();

    expect(listStore.destroy).toBeCalledWith();
    expect(excludeGhostsAndShadowsDisposerSpy).toBeCalledWith();
});
