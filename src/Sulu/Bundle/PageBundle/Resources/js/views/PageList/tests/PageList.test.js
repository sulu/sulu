// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {
    createRoute,
    createRouterMock,
    defaultWebspace,
    findElementByType,
    findWithHighOrderFunction,
    renderWithRef,
    waitForReaction,
} from 'sulu-admin-bundle/utils/TestHelper';

jest.mock('sulu-admin-bundle/containers', () => ({
    FlatStructureStrategy: require(
        'sulu-admin-bundle/containers/List/structureStrategies/FlatStructureStrategy'
    ).default,
    DefaultLoadingStrategy: require(
        'sulu-admin-bundle/containers/List/loadingStrategies/DefaultLoadingStrategy'
    ).default,
    List: require('sulu-admin-bundle/containers/List/List').default,
    ListStore: class {
        static getActiveSetting = jest.fn();

        constructor(resourceKey, listKey, userSettingsKey, observableOptions) {
            this.resourceKey = resourceKey;
            this.observableOptions = observableOptions;

            mockExtendObservable(this, {
                data: [],
            });
        }

        resourceKey;
        observableOptions;
        activeItems = [];
        filterOptions = {
            get: jest.fn().mockReturnValue({}),
        };
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
    formMetadataStore: {
        getSchemaTypes: jest.fn().mockReturnValue(Promise.resolve({types: {}})),
    },
    withToolbar: jest.fn((Component) => Component),
}));

jest.mock('sulu-admin-bundle/containers/List/registries/listAdapterRegistry', () => ({
    get: jest.fn().mockReturnValue(require('sulu-admin-bundle/containers/List/adapters/ColumnListAdapter').default),
    has: jest.fn().mockReturnValue(true),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    getPersistentSetting: jest.fn(),
}));

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    delete: jest.fn(),
}));

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.bind = jest.fn();
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.selections = [];
}));
jest.mock('sulu-admin-bundle/containers/ListOverlay', () => jest.fn().mockReturnValue(null));

jest.mock('sulu-website-bundle/containers/CacheClearToolbarAction', () => jest.fn(function() {
    this.getNode = jest.fn();
    this.getToolbarItemConfig = jest.fn();
}));

beforeEach(() => {
    jest.resetModules();
});

function createPageRouter(attributes: Object = {webspace: 'sulu'}) {
    return createRouterMock({
        attributes,
        route: createRoute({}, attributes, [], {name: 'sulu_page.page_list'}),
    });
}

test('Render PageList', () => {
    const formMetadataStore = require('sulu-admin-bundle/containers').formMetadataStore;
    const metadataPromise = Promise.resolve({types: {homepage: {}, example: {}}});
    formMetadataStore.getSchemaTypes.mockReturnValue(metadataPromise);

    const webspaceKey = observable.box('sulu');
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
    };

    const PageList = require('../PageList').default;
    const router = createPageRouter();

    const {container, instance: webspaceOverview} = renderWithRef(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    webspaceOverview.listStore.data.push(
        [
            {id: 1, title: 'Homepage', template: 'homepage'},
        ]
    );
    webspaceOverview.listStore.data.push(
        [
            {id: 2, title: 'Page 1', template: 'example'},
            {id: 3, title: 'Page 2', template: 'not-existing'},
        ]
    );

    return metadataPromise.then(() => {
        return waitForReaction().then(() => {
            expect(container).toMatchSnapshot();
        });
    });
});

test('Should show loader if available page types have not been loaded yet', () => {
    const formMetadataStore = require('sulu-admin-bundle/containers').formMetadataStore;
    const metadataPromise = Promise.resolve({types: {homepage: {}, example: {}}});
    formMetadataStore.getSchemaTypes.mockReturnValue(metadataPromise);

    const webspaceKey = observable.box('sulu');
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
    };

    const PageList = require('../PageList').default;
    const router = createPageRouter();

    const {instance: webspaceOverview} = renderWithRef(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    expect(findElementByType(webspaceOverview.render(), 'Loader')).toBeTruthy();

    return metadataPromise.then(() => {
        return waitForReaction().then(() => {
            expect(() => findElementByType(webspaceOverview.render(), 'Loader')).toThrow('Element not found');
        });
    });
});

test('Should show the locales from the webspace configuration for the toolbar', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const PageList = require('../PageList').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, PageList);

    const webspaceKey = observable.box('sulu');

    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
        key: 'sulu',
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
    };

    const router = createPageRouter();

    const {instance: webspaceOverview} = renderWithRef(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    webspaceOverview.locale.set('en');
    expect(webspaceOverview.locale.get()).toBe('en');

    const toolbarConfig = toolbarFunction.call(webspaceOverview);
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
    const PageList = require('../PageList').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, PageList);

    const webspaceKey = observable.box('sulu');

    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
        key: 'sulu',
    };

    const router = createPageRouter();

    const {instance: webspaceOverview} = renderWithRef(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const excludeGhostsAndShadows = webspaceOverview.excludeGhostsAndShadows;
    expect(excludeGhostsAndShadows.get()).toEqual(false);
    expect(webspaceOverview.listStore.observableOptions).toEqual(expect.objectContaining({
        'exclude-ghosts': excludeGhostsAndShadows,
        'exclude-shadows': excludeGhostsAndShadows,
    }));

    let toolbarConfig = toolbarFunction.call(webspaceOverview);
    expect(toolbarConfig.items[0].value).toEqual(true);

    toolbarConfig.items[0].onClick();
    toolbarConfig = toolbarFunction.call(webspaceOverview);
    expect(toolbarConfig.items[0].value).toEqual(false);
    expect(webspaceOverview.listStore.clear).toHaveBeenCalledWith();
    expect(webspaceOverview.excludeGhostsAndShadows.get()).toEqual(true);

    toolbarConfig.items[0].onClick();
    toolbarConfig = toolbarFunction.call(webspaceOverview);
    expect(toolbarConfig.items[0].value).toEqual(true);
    expect(webspaceOverview.excludeGhostsAndShadows.get()).toEqual(false);
});

test('Should set webspace if copied page is in different webspace than the source', () => {
    const formMetadataStore = require('sulu-admin-bundle/containers').formMetadataStore;
    const metadataPromise = Promise.resolve({types: {homepage: {}, example: {}}});
    formMetadataStore.getSchemaTypes.mockReturnValue(metadataPromise);

    const PageList = require('../PageList').default;

    const webspaceKey = observable.box('sulu');

    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
        key: 'sulu',
    };

    const router = createPageRouter();

    const {instance: webspaceOverview} = renderWithRef(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    return metadataPromise.then(() => {
        return waitForReaction().then(() => {
            findElementByType(webspaceOverview.render(), 'List').props.onCopyFinished({webspace: 'test'});
            expect(webspaceKey.get()).toEqual('test');
        });
    });
});

test('Should use CacheClearToolbarAction for cache clearing', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const PageList = require('../PageList').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, PageList);
    const CacheClearToolbarAction = require('sulu-website-bundle/containers').CacheClearToolbarAction;

    const webspaceKey = observable.box('sulu');

    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
        key: 'sulu',
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
    };

    const router = createPageRouter();

    const {instance: pageList} = renderWithRef(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const cacheClearToolbarAction: CacheClearToolbarAction = (CacheClearToolbarAction: any).mock.instances[0];

    expect(CacheClearToolbarAction).toHaveBeenCalledWith('sulu');
    expect(cacheClearToolbarAction.getNode).toHaveBeenCalledWith();

    expect(cacheClearToolbarAction.getToolbarItemConfig).not.toHaveBeenCalled();
    toolbarFunction.call(pageList);
    expect(cacheClearToolbarAction.getToolbarItemConfig).toHaveBeenCalled();
});

test('Should load webspace and active route attribute from listStore and userStore', () => {
    const PageList = require('../PageList').default;
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    const userStore = require('sulu-admin-bundle/stores').userStore;

    userStore.getPersistentSetting.mockImplementation((key) => {
        if (key === 'sulu_page.webspace_overview.webspace') {
            return 'sulu';
        }
    });

    ListStore.getActiveSetting.mockReturnValueOnce('some-uuid');

    // $FlowFixMe
    expect(PageList.getDerivedRouteAttributes(undefined, {webspace: 'abc'})).toEqual({
        active: 'some-uuid',
    });
    expect(ListStore.getActiveSetting).toHaveBeenCalledWith('pages', 'page_list_abc');
});

test('Destroy ListStore to avoid many requests and reset active to be set on webspace change', () => {
    const PageList = require('../PageList').default;

    const webspaceKey = observable.box('sulu');

    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
        key: 'sulu',
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
    };

    const router = createPageRouter();

    const {instance: webspaceOverview} = renderWithRef(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    webspaceKey.set('sulu_blog');

    expect(webspaceOverview.listStore.destroy).toHaveBeenCalledWith();
    expect(webspaceOverview.listStore.active.set).toHaveBeenCalledWith(undefined);
});

test('Should bind router', () => {
    const PageList = require('../PageList').default;

    const webspaceKey = observable.box('sulu');
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
    };

    const router = createPageRouter();

    const {instance: webspaceOverview} = renderWithRef(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );
    const page = webspaceOverview.page;
    const locale = webspaceOverview.locale;
    const excludeGhostsAndShadows = webspaceOverview.excludeGhostsAndShadows;

    expect(router.bind).toHaveBeenCalledWith('page', page, 1);
    expect(router.bind).toHaveBeenCalledWith('excludeGhostsAndShadows', excludeGhostsAndShadows, false);
    expect(router.bind).toHaveBeenCalledWith('locale', locale);
    expect(router.bind).toHaveBeenCalledWith('active', webspaceOverview.listStore.active);
});

test('Should call disposers on unmount', () => {
    const PageList = require('../PageList').default;

    const webspaceKey = observable.box('sulu');
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
    };

    const router = createPageRouter();

    const {instance: webspaceOverview, unmount} = renderWithRef(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const listStore = webspaceOverview.listStore;

    const excludeGhostsAndShadowsDisposerSpy = jest.fn();
    webspaceOverview.excludeGhostsAndShadowsDisposer = excludeGhostsAndShadowsDisposerSpy;
    unmount();

    expect(listStore.destroy).toHaveBeenCalledWith();
    expect(excludeGhostsAndShadowsDisposerSpy).toHaveBeenCalledWith();
});
