// @flow
import React from 'react';
import {act, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import {
    createDeferred,
    createRoute,
    createRouterMock,
    defaultWebspace,
    mockResizeObserver,
} from 'sulu-admin-bundle/utils/TestHelper';

const mockListStoreInstances = [];
let mockListProps;

mockResizeObserver();

jest.mock('sulu-admin-bundle/containers', () => {
    const React = require('react');
    const {extendObservable} = require('mobx');
    const List = require('sulu-admin-bundle/containers/List/List').default;
    const ListStore = jest.fn(function(resourceKey, listKey, userSettingsKey, observableOptions) {
        this.resourceKey = resourceKey;
        this.observableOptions = observableOptions;
        this.activeItems = [];
        this.filterOptions = {
            get: jest.fn().mockReturnValue({}),
        };
        this.active = {
            get: jest.fn(),
            set: jest.fn(),
        };
        this.sortColumn = {
            get: jest.fn(),
        };
        this.sortOrder = {
            get: jest.fn(),
        };
        this.limit = {
            get: jest.fn().mockReturnValue(10),
        };
        this.setLimit = jest.fn();
        this.selections = [];
        this.selectionIds = [];
        this.getPage = jest.fn().mockReturnValue(1);
        this.clear = jest.fn();
        this.destroy = jest.fn();
        this.sendRequest = jest.fn();
        this.updateLoadingStrategy = jest.fn();
        this.updateStructureStrategy = jest.fn();

        extendObservable(this, {data: []});
        mockListStoreInstances.push(this);
    });

    (ListStore: any).getActiveSetting = jest.fn();

    return {
        formMetadataStore: {
            getSchemaTypes: jest.fn().mockReturnValue(Promise.resolve({types: {}})),
        },
        FlatStructureStrategy: require(
            'sulu-admin-bundle/containers/List/structureStrategies/FlatStructureStrategy'
        ).default,
        DefaultLoadingStrategy: require(
            'sulu-admin-bundle/containers/List/loadingStrategies/DefaultLoadingStrategy'
        ).default,
        List: jest.fn((props) => {
            mockListProps = props;
            return <List {...props} />;
        }),
        ListStore,
        withToolbar: require('sulu-admin-bundle/containers/Toolbar/withToolbar').default,
    };
});

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    getPersistentSetting: jest.fn(),
}));

jest.mock('sulu-admin-bundle/containers/List/registries/listAdapterRegistry', () => ({
    get: jest.fn().mockReturnValue(require('sulu-admin-bundle/containers/List/adapters/ColumnListAdapter').default),
    has: jest.fn().mockReturnValue(true),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => jest.fn(() => null));
jest.mock('sulu-admin-bundle/containers/ListOverlay', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.selections = [];
}));

jest.mock('sulu-admin-bundle/utils/Translator');

const mockGetCacheClearNode = jest.fn();
const mockGetCacheClearToolbarItemConfig = jest.fn().mockReturnValue({label: 'clear-cache'});

jest.mock('sulu-website-bundle/containers/CacheClearToolbarAction', () => jest.fn(function() {
    this.getNode = mockGetCacheClearNode;
    this.getToolbarItemConfig = mockGetCacheClearToolbarItemConfig;
}));

beforeEach(() => {
    jest.clearAllMocks();
    jest.resetModules();
    mockListStoreInstances.length = 0;
    mockListProps = undefined;
});

function createPageRouter(attributes: Object = {webspace: 'sulu'}) {
    const router = createRouterMock({
        attributes,
        route: createRoute({}, attributes, [], {name: 'sulu_page.page_list'}),
    });
    router.addUpdateRouteHook.mockReturnValue(jest.fn());

    return router;
}

function getBoundValue(router, attributeName) {
    return router.bind.mock.calls.find(([name]) => name === attributeName)[1];
}

function getListProps(): Object {
    if (!mockListProps) {
        throw new Error('Expected list props');
    }

    return mockListProps;
}

function renderPageList(webspace: Object = {...defaultWebspace, localizations: undefined}) {
    const PageList = require('../PageList').default;
    const Toolbar = require('sulu-admin-bundle/containers/Toolbar').default;
    const router = createPageRouter();
    const webspaceKey = observable.box('sulu');
    render(<Toolbar />);
    const view = render(
        <PageList
            route={router.route}
            router={router}
            // $FlowFixMe
            webspace={webspace}
            webspaceKey={webspaceKey}
        />
    );

    return {...view, router, webspaceKey};
}

test('Render PageList', async() => {
    const formMetadataStore = require('sulu-admin-bundle/containers').formMetadataStore;
    const metadataPromise = Promise.resolve({types: {homepage: {}, example: {}}});
    formMetadataStore.getSchemaTypes.mockReturnValue(metadataPromise);

    const {container} = renderPageList();
    const listStore = mockListStoreInstances[0];

    act(() => {
        listStore.data.push([{id: 1, title: 'Homepage', template: 'homepage'}]);
        listStore.data.push([
            {id: 2, title: 'Page 1', template: 'example'},
            {id: 3, title: 'Page 2', template: 'not-existing'},
        ]);
    });

    await metadataPromise;
    expect(await screen.findByLabelText('Homepage')).toBeInTheDocument();
    expect(screen.getByLabelText('Page 2')).toBeInTheDocument();
    expect(screen.getByLabelText('su-exclamation-circle')).toBeInTheDocument();
    expect(container).toMatchSnapshot();
});

test('Should show loader if available page types have not been loaded yet', async() => {
    const metadataRequest = createDeferred<Object>();
    const formMetadataStore = require('sulu-admin-bundle/containers').formMetadataStore;
    formMetadataStore.getSchemaTypes.mockReturnValue(metadataRequest.promise);

    const {container} = renderPageList();

    expect(container.querySelector('.spinner')).toBeInTheDocument();

    await act(async() => metadataRequest.resolve({types: {homepage: {}, example: {}}}));
    await waitFor(() => expect(container.querySelector('.spinner')).not.toBeInTheDocument());
    expect(container.querySelector('.listContainer')).toBeInTheDocument();
});

test('Should allow adding and copying pages when the webspace grants the add permission', async() => {
    const formMetadataStore = require('sulu-admin-bundle/containers').formMetadataStore;
    const metadataPromise = Promise.resolve({types: {homepage: {}, example: {}}});
    formMetadataStore.getSchemaTypes.mockReturnValue(metadataPromise);
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
        _permissions: {add: true},
    };

    renderPageList(webspace);
    await metadataPromise;
    await waitFor(() => expect(mockListProps).toBeDefined());
    const listProps = getListProps();

    expect(listProps.onItemAdd).toBeInstanceOf(Function);
    expect(listProps.copyable).toEqual(true);
});

test('Should not allow adding and copying pages without the add permission on the webspace', async() => {
    const formMetadataStore = require('sulu-admin-bundle/containers').formMetadataStore;
    const metadataPromise = Promise.resolve({types: {homepage: {}, example: {}}});
    formMetadataStore.getSchemaTypes.mockReturnValue(metadataPromise);
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
        _permissions: {add: false},
    };

    renderPageList(webspace);
    await metadataPromise;
    await waitFor(() => expect(mockListProps).toBeDefined());
    const listProps = getListProps();

    expect(listProps.onItemAdd).toBeUndefined();
    expect(listProps.copyable).toEqual(false);
});

test('Should show the error of a failed copy in the toolbar', async() => {
    const user = userEvent.setup();
    const formMetadataStore = require('sulu-admin-bundle/containers').formMetadataStore;
    const metadataPromise = Promise.resolve({types: {homepage: {}, example: {}}});
    formMetadataStore.getSchemaTypes.mockReturnValue(metadataPromise);
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
        _permissions: {add: true},
    };

    renderPageList(webspace);
    await metadataPromise;
    await waitFor(() => expect(mockListProps).toBeDefined());
    const listProps = getListProps();

    act(() => listProps.onCopyError({detail: 'Copying is not allowed'}));

    expect(screen.getByRole('button', {name: /Copying is not allowed/})).toBeInTheDocument();

    await user.click(screen.getByLabelText('su-times'));
    act(() => listProps.onCopyError({}));

    expect(screen.getByRole('button', {name: /sulu_admin.unexpected_copy_server_error/})).toBeInTheDocument();
});

test('Should show the locales from the webspace configuration for the toolbar', async() => {
    const user = userEvent.setup();
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
        key: 'sulu',
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
    };
    const {router} = renderPageList(webspace);

    act(() => getBoundValue(router, 'locale').set('en'));

    const localeSelect = screen.getByRole('button', {name: /^en/});
    expect(localeSelect).toBeInTheDocument();

    await user.click(localeSelect);

    expect(screen.getByRole('button', {name: /^su-check en/})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'de'})).toBeInTheDocument();
});

test('Should change excludeGhostsAndShadows when value of toggler is changed', async() => {
    const user = userEvent.setup();
    const webspace = {
        ...defaultWebspace,
        localizations: undefined,
        key: 'sulu',
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
    };
    const {router} = renderPageList(webspace);
    const listStore = mockListStoreInstances[0];
    const excludeGhostsAndShadows = getBoundValue(router, 'excludeGhostsAndShadows');

    expect(excludeGhostsAndShadows.get()).toBe(false);
    expect(listStore.observableOptions).toEqual(expect.objectContaining({
        'exclude-ghosts': excludeGhostsAndShadows,
        'exclude-shadows': excludeGhostsAndShadows,
    }));
    const toggler = screen.getByRole('checkbox', {name: 'sulu_page.show_ghost_and_shadow'});
    expect(toggler).toBeChecked();

    await user.click(toggler);

    expect(toggler).not.toBeChecked();
    expect(listStore.clear).toHaveBeenCalledWith();
    expect(excludeGhostsAndShadows.get()).toBe(true);

    await user.click(toggler);

    expect(toggler).toBeChecked();
    expect(excludeGhostsAndShadows.get()).toBe(false);
});

test('Should set webspace if copied page is in different webspace than the source', async() => {
    const {webspaceKey} = renderPageList({
        ...defaultWebspace,
        localizations: undefined,
        key: 'sulu',
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
    });

    await waitFor(() => expect(mockListProps).toBeDefined());
    const listProps = getListProps();
    act(() => listProps.onCopyFinished({webspace: 'test'}));

    expect(webspaceKey.get()).toBe('test');
});

test('Should use CacheClearToolbarAction for cache clearing', () => {
    const CacheClearToolbarAction: any = require(
        'sulu-website-bundle/containers'
    ).CacheClearToolbarAction;

    renderPageList({
        ...defaultWebspace,
        localizations: undefined,
        key: 'sulu',
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
    });

    expect(CacheClearToolbarAction).toHaveBeenCalledWith('sulu');
    expect(mockGetCacheClearNode).toHaveBeenCalledWith();
    expect(mockGetCacheClearToolbarItemConfig).toHaveBeenCalledWith();
    expect(screen.getByRole('button', {name: 'clear-cache'})).toBeInTheDocument();
});

test('Should load active route attribute from ListStore', () => {
    const PageList = require('../PageList').default;
    const ListStore = require('sulu-admin-bundle/containers').ListStore;
    ListStore.getActiveSetting.mockReturnValueOnce('some-uuid');

    // $FlowFixMe
    expect(PageList.getDerivedRouteAttributes(undefined, {webspace: 'abc'})).toEqual({
        active: 'some-uuid',
    });
    expect(ListStore.getActiveSetting).toHaveBeenCalledWith('pages', 'page_list_abc');
});

test('Destroy ListStore and reset active on webspace change', () => {
    const {webspaceKey} = renderPageList({
        ...defaultWebspace,
        localizations: undefined,
        key: 'sulu',
        allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
    });
    const listStore = mockListStoreInstances[0];

    act(() => webspaceKey.set('sulu_blog'));

    expect(listStore.destroy).toHaveBeenCalledWith();
    expect(listStore.active.set).toHaveBeenCalledWith(undefined);
});

test('Should bind router attributes', () => {
    const {router} = renderPageList();
    const listStore = mockListStoreInstances[0];

    expect(router.bind).toHaveBeenCalledWith('page', expect.any(Object), 1);
    expect(router.bind).toHaveBeenCalledWith('excludeGhostsAndShadows', expect.any(Object), false);
    expect(router.bind).toHaveBeenCalledWith('locale', expect.any(Object));
    expect(router.bind).toHaveBeenCalledWith('active', listStore.active);
});

test('Should destroy ListStore and stop reacting on unmount', () => {
    const {router, unmount} = renderPageList();
    const listStore = mockListStoreInstances[0];
    const excludeGhostsAndShadows = getBoundValue(router, 'excludeGhostsAndShadows');

    unmount();

    expect(listStore.destroy).toHaveBeenCalledWith();
    listStore.clear.mockClear();
    act(() => excludeGhostsAndShadows.set(true));
    expect(listStore.clear).not.toHaveBeenCalled();
});
