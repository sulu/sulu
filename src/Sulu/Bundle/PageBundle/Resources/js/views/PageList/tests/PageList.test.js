// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {Router} from 'sulu-admin-bundle/services';
import {defaultWebspace} from 'sulu-admin-bundle/utils/TestHelper';

const mockInterceptDisposers: Array<any> = [];
const mockListPropsCalls: Array<any> = [];
const mockListStores: Array<any> = [];
const mockToolbarConfigGetters: Array<() => any> = [];

jest.mock('mobx', () => {
    const actualMobx = jest.requireActual('mobx');

    return {
        ...actualMobx,
        intercept: jest.fn((...args) => {
            const disposer = jest.fn(actualMobx.intercept(...args));
            mockInterceptDisposers.push(disposer);

            return disposer;
        }),
    };
});

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const actual = jest.requireActual('sulu-admin-bundle/components');

    return {
        ...actual,
        Loader: jest.fn(() => <div data-testid="loader" />),
    };
});

jest.mock('sulu-admin-bundle/containers', () => {
    const React = require('react');
    const ActualList = require('sulu-admin-bundle/containers/List/List').default;

    return {
        FlatStructureStrategy: require(
            'sulu-admin-bundle/containers/List/structureStrategies/FlatStructureStrategy'
        ).default,
        DefaultLoadingStrategy: require(
            'sulu-admin-bundle/containers/List/loadingStrategies/DefaultLoadingStrategy'
        ).default,
        List: jest.fn((props) => {
            mockListPropsCalls.push(props);

            return <ActualList {...props} />;
        }),
        ListStore: class {
            static getActiveSetting = jest.fn();

            constructor(resourceKey, listKey, userSettingsKey, observableOptions) {
                this.resourceKey = resourceKey;
                this.observableOptions = observableOptions;

                mockExtendObservable(this, {
                    data: [],
                });

                mockListStores.push(this);
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
            data: Array<any>;
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
        withToolbar: jest.fn((Component, toolbar) => {
            return class WithToolbarMock extends Component {
                render() {
                    mockToolbarConfigGetters.push(() => toolbar.call(this));

                    return super.render();
                }
            };
        }),
    };
});

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

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.selections = [];
}));

jest.mock('sulu-admin-bundle/containers/ListOverlay', () => jest.fn().mockReturnValue(null));

jest.mock('sulu-website-bundle/containers/CacheClearToolbarAction', () => jest.fn(function() {
    this.getNode = jest.fn();
    this.getToolbarItemConfig = jest.fn();
}));

const getLatestListProps = () => mockListPropsCalls[mockListPropsCalls.length - 1];
const getLatestListStore = () => mockListStores[mockListStores.length - 1];
const getLatestToolbarConfig = () => mockToolbarConfigGetters[mockToolbarConfigGetters.length - 1]();

beforeEach(() => {
    jest.resetModules();
    jest.clearAllMocks();
    mockInterceptDisposers.splice(0, mockInterceptDisposers.length);
    mockListPropsCalls.splice(0, mockListPropsCalls.length);
    mockListStores.splice(0, mockListStores.length);
    mockToolbarConfigGetters.splice(0, mockToolbarConfigGetters.length);
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

    const {asFragment} = render(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const listStore = getLatestListStore();

    listStore.data.push(
        [
            {id: 1, title: 'Homepage', template: 'homepage'},
        ]
    );
    listStore.data.push(
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

    render(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    act(() => {
        getLatestToolbarConfig().locale.onChange('en');
    });

    const toolbarConfig = getLatestToolbarConfig();
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

    render(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const listStore = getLatestListStore();
    const excludeGhostsAndShadows = listStore.observableOptions['exclude-ghosts'];

    expect(excludeGhostsAndShadows.get()).toEqual(false);
    expect(listStore.observableOptions).toEqual(expect.objectContaining({
        'exclude-ghosts': excludeGhostsAndShadows,
        'exclude-shadows': excludeGhostsAndShadows,
    }));

    let toolbarConfig = getLatestToolbarConfig();
    expect(toolbarConfig.items[0].value).toEqual(true);

    act(() => {
        toolbarConfig.items[0].onClick();
    });
    toolbarConfig = getLatestToolbarConfig();
    expect(toolbarConfig.items[0].value).toEqual(false);
    expect(listStore.clear).toHaveBeenCalledWith();
    expect(excludeGhostsAndShadows.get()).toEqual(true);

    act(() => {
        toolbarConfig.items[0].onClick();
    });
    toolbarConfig = getLatestToolbarConfig();
    expect(toolbarConfig.items[0].value).toEqual(true);
    expect(excludeGhostsAndShadows.get()).toEqual(false);
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

    render(
        <PageList
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

    act(() => {
        getLatestListProps().onCopyFinished({webspace: 'test'});
    });

    expect(webspaceKey.get()).toEqual('test');
});

test('Should use CacheClearToolbarAction for cache clearing', () => {
    const PageList = require('../PageList').default;
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

    render(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const cacheClearToolbarAction = (CacheClearToolbarAction: any).mock.instances[0];

    expect(CacheClearToolbarAction).toHaveBeenCalledWith('sulu');
    expect(cacheClearToolbarAction.getNode).toHaveBeenCalledWith();
    expect(getLatestToolbarConfig().items).toContain(
        cacheClearToolbarAction.getToolbarItemConfig.mock.results[0].value
    );
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

    render(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const listStore = getLatestListStore();

    webspaceKey.set('sulu_blog');

    expect(listStore.destroy).toHaveBeenCalledWith();
    expect(listStore.active.set).toHaveBeenCalledWith(undefined);
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

    render(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const listStore = getLatestListStore();

    expect(router.bind).toHaveBeenCalledWith('page', listStore.observableOptions.page, 1);
    expect(router.bind).toHaveBeenCalledWith(
        'excludeGhostsAndShadows',
        listStore.observableOptions['exclude-ghosts'],
        false
    );
    expect(router.bind).toHaveBeenCalledWith('locale', listStore.observableOptions.locale);
    expect(router.bind).toHaveBeenCalledWith('active', listStore.active);
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

    const {unmount} = render(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    const listStore = getLatestListStore();

    unmount();

    expect(listStore.destroy).toHaveBeenCalledWith();
    expect(mockInterceptDisposers).toHaveLength(2);
    expect(mockInterceptDisposers[0]).toHaveBeenCalledWith();
    expect(mockInterceptDisposers[1]).toHaveBeenCalledWith();
});
