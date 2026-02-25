// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {Router} from 'sulu-admin-bundle/services';
import {findWithHighOrderFunction, defaultWebspace} from 'sulu-admin-bundle/utils/TestHelper';

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const actual = jest.requireActual('sulu-admin-bundle/components');

    return {
        ...actual,
        Loader: jest.fn(() => <div data-testid="loader" />),
    };
});

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

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

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

test('Render PageList', async() => {
    const formMetadataStore = require('sulu-admin-bundle/containers').formMetadataStore;
    const metadataPromise = Promise.resolve({types: {homepage: {}, example: {}}});
    formMetadataStore.getSchemaTypes.mockReturnValue(metadataPromise);

    const webspaceKey = observable.box('sulu');
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
    };

    const PageList = require('../PageList').default;
    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const pageListRef = React.createRef();
    const {asFragment} = render(
        <PageList
            ref={pageListRef}
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const pageListInstance = (pageListRef.current: any);

    pageListInstance.listStore.data.push(
        [
            {id: 1, title: 'Homepage', template: 'homepage'},
        ]
    );
    pageListInstance.listStore.data.push(
        [
            {id: 2, title: 'Page 1', template: 'example'},
            {id: 3, title: 'Page 2', template: 'not-existing'},
        ]
    );

    await act(async() => {
        await metadataPromise;
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Should show loader if available page types have not been loaded yet', async() => {
    const formMetadataStore = require('sulu-admin-bundle/containers').formMetadataStore;
    const metadataPromise = Promise.resolve({types: {homepage: {}, example: {}}});
    formMetadataStore.getSchemaTypes.mockReturnValue(metadataPromise);

    const webspaceKey = observable.box('sulu');
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
    };

    const PageList = require('../PageList').default;
    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    render(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    expect(screen.getByTestId('loader')).toBeInTheDocument();

    await act(async() => {
        await metadataPromise;
    });

    expect(screen.queryByTestId('loader')).not.toBeInTheDocument();
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

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const pageListRef = React.createRef();

    render(
        <PageList
            ref={pageListRef}
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const pageListInstance = (pageListRef.current: any);

    pageListInstance.locale.set('en');
    expect(pageListInstance.locale.get()).toBe('en');

    const toolbarConfig = toolbarFunction.call(pageListInstance);
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

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const pageListRef = React.createRef();

    render(
        <PageList
            ref={pageListRef}
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const pageListInstance = (pageListRef.current: any);

    const excludeGhostsAndShadows = pageListInstance.excludeGhostsAndShadows;
    expect(excludeGhostsAndShadows.get()).toEqual(false);
    expect(pageListInstance.listStore.observableOptions).toEqual(expect.objectContaining({
        'exclude-ghosts': excludeGhostsAndShadows,
        'exclude-shadows': excludeGhostsAndShadows,
    }));

    let toolbarConfig = toolbarFunction.call(pageListInstance);
    expect(toolbarConfig.items[0].value).toEqual(true);

    toolbarConfig.items[0].onClick();
    toolbarConfig = toolbarFunction.call(pageListInstance);
    expect(toolbarConfig.items[0].value).toEqual(false);
    expect(pageListInstance.listStore.clear).toHaveBeenCalledWith();
    expect(pageListInstance.excludeGhostsAndShadows.get()).toEqual(true);

    toolbarConfig.items[0].onClick();
    toolbarConfig = toolbarFunction.call(pageListInstance);
    expect(toolbarConfig.items[0].value).toEqual(true);
    expect(pageListInstance.excludeGhostsAndShadows.get()).toEqual(false);
});

test('Should set webspace if copied page is in different webspace than the source', async() => {
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

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const pageListRef = React.createRef();

    render(
        <PageList
            ref={pageListRef}
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    await act(async() => {
        await metadataPromise;
    });

    const pageListInstance = (pageListRef.current: any);
    pageListInstance.handleCopyFinished({webspace: 'test'});

    expect(webspaceKey.get()).toEqual('test');
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

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const pageListRef = React.createRef();

    render(
        <PageList
            ref={pageListRef}
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const pageListInstance = (pageListRef.current: any);
    const cacheClearToolbarAction = (CacheClearToolbarAction: any).mock.instances[0];

    expect(CacheClearToolbarAction).toHaveBeenCalledWith('sulu');
    expect(cacheClearToolbarAction.getNode).toHaveBeenCalledWith();

    expect(cacheClearToolbarAction.getToolbarItemConfig).not.toHaveBeenCalled();
    toolbarFunction.call(pageListInstance);
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

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const pageListRef = React.createRef();

    render(
        <PageList
            ref={pageListRef}
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const pageListInstance = (pageListRef.current: any);

    webspaceKey.set('sulu_blog');

    expect(pageListInstance.listStore.destroy).toHaveBeenCalledWith();
    expect(pageListInstance.listStore.active.set).toHaveBeenCalledWith(undefined);
});

test('Should bind router', () => {
    const PageList = require('../PageList').default;

    const webspaceKey = observable.box('sulu');
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
    };

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const pageListRef = React.createRef();

    render(
        <PageList
            ref={pageListRef}
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const pageListInstance = (pageListRef.current: any);
    const page = pageListInstance.page;
    const locale = pageListInstance.locale;
    const excludeGhostsAndShadows = pageListInstance.excludeGhostsAndShadows;

    expect(router.bind).toHaveBeenCalledWith('page', page, 1);
    expect(router.bind).toHaveBeenCalledWith('excludeGhostsAndShadows', excludeGhostsAndShadows, false);
    expect(router.bind).toHaveBeenCalledWith('locale', locale);
    expect(router.bind).toHaveBeenCalledWith('active', pageListInstance.listStore.active);
});

test('Should call disposers on unmount', () => {
    const PageList = require('../PageList').default;

    const webspaceKey = observable.box('sulu');
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
    };

    const router = new Router({});
    router.attributes = {
        webspace: 'sulu',
    };

    const pageListRef = React.createRef();

    const {unmount} = render(
        <PageList
            ref={pageListRef}
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const pageListInstance = (pageListRef.current: any);
    const listStore = pageListInstance.listStore;

    const excludeGhostsAndShadowsDisposerSpy = jest.fn();
    pageListInstance.excludeGhostsAndShadowsDisposer = excludeGhostsAndShadowsDisposerSpy;

    unmount();

    expect(listStore.destroy).toHaveBeenCalledWith();
    expect(excludeGhostsAndShadowsDisposerSpy).toHaveBeenCalledWith();
});
