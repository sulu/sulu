/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import List from '../List';
import getMockCallArg from '../../../utils/TestHelper/getMockCallArg';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => `translated:${key}`),
}));

jest.mock('../../../containers/List', () => {
    const React = require('react');
    const {observable} = require('mobx');
    const ListContainer = jest.fn(function ListContainer({header}) {
        return <div data-testid="list-container">{header}</div>;
    });
    const ListStore = jest.fn(function(
        resourceKey,
        listKey,
        userSettingsKey,
        observableOptions,
        options,
        metadataOptions
    ) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.userSettingsKey = userSettingsKey;
        this.observableOptions = observableOptions;
        this.options = options;
        this.metadataOptions = metadataOptions;
        this.active = observable.box(undefined);
        this.sortColumn = observable.box(undefined);
        this.sortOrder = observable.box(undefined);
        this.searchTerm = observable.box(undefined);
        this.limit = observable.box(undefined);
        this.filterOptions = observable.box(undefined);
        this.reload = jest.fn();
        this.destroy = jest.fn();
    });

    ListStore.getLimitSetting = jest.fn(() => 10);
    ListStore.getActiveSetting = jest.fn(() => 'all');
    ListStore.getFilterSetting = jest.fn(() => ({published: true}));
    ListStore.getSortColumnSetting = jest.fn(() => 'title');
    ListStore.getSortOrderSetting = jest.fn(() => 'asc');

    return {
        __esModule: true,
        default: ListContainer,
        ListStore,
    };
});

jest.mock('../registries/listToolbarActionRegistry', () => ({
    __esModule: true,
    default: {
        get: jest.fn(),
    },
}));

jest.mock('../registries/listItemActionRegistry', () => ({
    __esModule: true,
    default: {
        get: jest.fn(),
    },
}));

const {default: ListContainerMock, ListStore: ListStoreMock} = jest.requireMock('../../../containers/List');
const listToolbarActionRegistry = jest.requireMock('../registries/listToolbarActionRegistry').default;
const listItemActionRegistry = jest.requireMock('../registries/listItemActionRegistry').default;

const createRouter = (options = {}, attributes = {}) => ({
    attributes,
    bind: jest.fn(),
    navigate: jest.fn(),
    restore: jest.fn(),
    route: {
        options: {
            adapters: ['table'],
            listKey: 'snippets',
            resourceKey: 'snippets',
            ...options,
        },
    },
});

beforeEach(() => {
    jest.clearAllMocks();
    listToolbarActionRegistry.get.mockReset();
    listItemActionRegistry.get.mockReset();
});

test('renders list view with title from props', () => {
    const router = createRouter();

    const {asFragment} = render(<List router={router} title="My List" />);

    expect(ListContainerMock).toHaveBeenCalled();
    expect(screen.getByText('My List')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('uses translated route title when configured', () => {
    const router = createRouter({title: 'sulu_snippet.snippets'});

    render(<List router={router} title="Ignored title" />);

    expect(screen.getByText('translated:sulu_snippet.snippets')).toBeInTheDocument();
});

test('throws when mandatory route options are missing', () => {
    const routerWithoutResourceKey = createRouter({resourceKey: undefined});
    const routerWithoutListKey = createRouter({listKey: undefined});
    const routerWithoutAdapters = createRouter({adapters: undefined});

    expect(() => render(<List router={routerWithoutResourceKey} />))
        .toThrow('mandatory "resourceKey" option');
    expect(() => render(<List router={routerWithoutListKey} />))
        .toThrow('mandatory "listKey" option');
    expect(() => render(<List router={routerWithoutAdapters} />))
        .toThrow('mandatory "adapters" option');
});

test('builds list and metadata request options from router and resource store', () => {
    const router = createRouter(
        {
            requestParameters: {flat: true},
            routerAttributesToListRequest: {locale: 'requestLocale'},
            resourceStorePropertiesToListRequest: {id: 'parentId'},
            routerAttributesToListMetadata: {mode: 'metadataMode'},
            resourceStorePropertiesToListMetadata: {id: 'metadataParentId'},
            metadataRequestParameters: {includeFields: true},
        },
        {
            locale: 'de',
            mode: 'compact',
        }
    );
    const resourceStore = {
        data: {
            id: 42,
        },
    };

    render(<List resourceStore={resourceStore} router={router} />);

    const observableOptions = getMockCallArg(ListStoreMock, 0, 3);
    const listOptions = getMockCallArg(ListStoreMock, 0, 4);
    const metadataOptions = getMockCallArg(ListStoreMock, 0, 5);
    expect(observableOptions).toEqual({page: expect.anything()});
    expect(listOptions).toEqual({
        flat: true,
        parentId: 42,
        requestLocale: 'de',
    });
    expect(metadataOptions).toEqual({
        includeFields: true,
        metadataMode: 'compact',
        metadataParentId: 42,
    });
});

test('navigates to add and edit views through list callbacks', () => {
    const locale = observable.box('en');
    const router = createRouter({addView: 'add_form', editView: 'edit_form', locales: ['en', 'de']});

    render(<List locale={locale} router={router} />);
    const listContainerProps = getLatestMockProps(ListContainerMock);

    listContainerProps.onItemAdd(11);
    listContainerProps.onItemClick(7);

    expect(router.navigate).toHaveBeenNthCalledWith(1, 'add_form', {locale: 'en', parentId: 11});
    expect(router.navigate).toHaveBeenNthCalledWith(2, 'edit_form', {id: 7, locale: 'en'});
});

test('prefers explicit item callbacks over route navigation', () => {
    const locale = observable.box('en');
    const onItemAdd = jest.fn();
    const onItemClick = jest.fn();
    const router = createRouter({addView: 'add_form', editView: 'edit_form', locales: ['en', 'de']});

    render(
        <List
            locale={locale}
            onItemAdd={onItemAdd}
            onItemClick={onItemClick}
            router={router}
        />
    );

    const listContainerProps = getLatestMockProps(ListContainerMock);
    listContainerProps.onItemAdd(1);
    listContainerProps.onItemClick(2);

    expect(onItemAdd).toHaveBeenCalledWith(1);
    expect(onItemClick).toHaveBeenCalledWith(2);
    expect(router.navigate).not.toHaveBeenCalled();
});

test('destroys list store and toolbar actions on unmount', () => {
    const toolbarActionDestroy = jest.fn();
    const toolbarActionNode = <div key="toolbar-node">Toolbar action node</div>;
    const ToolbarAction = jest.fn(function() {
        this.getNode = jest.fn(() => toolbarActionNode);
        this.getToolbarItemConfig = jest.fn(() => ({}));
        this.setLocales = jest.fn();
        this.destroy = toolbarActionDestroy;
    });
    listToolbarActionRegistry.get.mockReturnValue(ToolbarAction);
    listItemActionRegistry.get.mockReturnValue(jest.fn(function() {
        this.getNode = jest.fn(() => null);
        this.getItemActionConfig = jest.fn(() => ({}));
        this.setLocales = jest.fn();
        this.destroy = jest.fn();
    }));

    const router = createRouter({
        locales: ['en'],
        toolbarActions: [{type: 'mock-toolbar', options: {}}],
        itemActions: [{type: 'mock-item', options: {}}],
    });

    const {unmount} = render(<List router={router} />);
    unmount();

    expect(ListStoreMock.mock.instances[0].destroy).toHaveBeenCalled();
    expect(toolbarActionDestroy).toHaveBeenCalled();
});

test('derives route attributes from list store settings', () => {
    const route = {
        options: {
            listKey: 'snippets',
            userSettingsKey: 'custom',
        },
    };

    expect(List.getDerivedRouteAttributes(route)).toEqual({
        active: 'all',
        filter: {published: true},
        sortColumn: 'title',
        sortOrder: 'asc',
        limit: undefined,
    });
});
