/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {act, render, waitFor} from '@testing-library/react';
import {extendObservable, observable} from 'mobx';
import ResourceTabs from '../ResourceTabs';
import Tabs from '../../Tabs';
import ResourceStore from '../../../stores/ResourceStore';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';
import getMockCallArg from '../../../utils/TestHelper/getMockCallArg';

jest.mock('../../Tabs', () => jest.fn(function TabsMock({children}) {
    return <div data-testid="tabs">{children ? children() : null}</div>;
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn());

const TabsMock = Tabs;
const ResourceStoreMock = ResourceStore;

function createRoute(children, options = {}) {
    return {
        children,
        options: {
            resourceKey: 'test',
            ...options,
        },
    };
}

function createRouter(route, attributes = {id: 1}) {
    const updateHooks = [];

    return {
        _updateHooks: updateHooks,
        addUpdateRouteHook: jest.fn((updateHook) => {
            updateHooks.push(updateHook);
            return jest.fn();
        }),
        attributes,
        bind: jest.fn(),
        navigate: jest.fn(),
        redirect: jest.fn(),
        route,
    };
}

function mockResourceStore(options = {}) {
    const {
        data = {},
        initialized = true,
        loading = false,
    } = options;

    ResourceStoreMock.mockImplementation(function(resourceKey, id, resourceOptions = {}) {
        this.destroy = jest.fn();
        this.id = id;
        this.initialized = initialized;
        this.loading = loading;
        this.reload = jest.fn();
        this.resourceKey = resourceKey;
        this.resourceOptions = resourceOptions;

        extendObservable(this, {
            data,
        });
    });
}

function renderResourceTabs(props = {}) {
    const childRoute1 = {name: 'route1', options: {tabTitle: 'tabTitle1'}};
    const route = props.route || createRoute([childRoute1]);
    const router = props.router || createRouter(route);
    const children = props.children || (() => null);

    const view = render(
        <ResourceTabs
            {...props}
            route={route}
            router={router}
        >
            {children}
        </ResourceTabs>
    );

    return {
        ...view,
        route,
        router,
    };
}

function getTabsProps() {
    return getLatestMockProps(TabsMock);
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('passes title from resource store using route titleProperty', async() => {
    mockResourceStore({data: {test1: 'value1'}});

    const childRoute = {name: 'route1', options: {tabTitle: 'tabTitle1'}};
    const route = createRoute([childRoute], {titleProperty: 'test1'});
    const router = createRouter(childRoute);
    const children = jest.fn(() => null);

    renderResourceTabs({children, route, router});

    await waitFor(() => {
        expect(children).toBeCalledWith({
            locales: undefined,
            resourceStore: expect.anything(),
            title: 'value1',
        });
    });
});

test('does not pass title if no titleProperty is configured', async() => {
    mockResourceStore({data: {test1: 'value1'}});

    const childRoute = {name: 'route1', options: {tabTitle: 'tabTitle1'}};
    const route = createRoute([childRoute]);
    const router = createRouter(childRoute);
    const children = jest.fn(() => null);

    renderResourceTabs({children, route, router});

    await waitFor(() => {
        expect(children).toBeCalledWith({
            locales: undefined,
            resourceStore: expect.anything(),
            title: undefined,
        });
    });
});

test('uses titleProperty from props over route option', async() => {
    mockResourceStore({data: {test1: 'value1', test2: 'value2'}});

    const childRoute = {name: 'route1', options: {tabTitle: 'tabTitle1'}};
    const route = createRoute([childRoute], {titleProperty: 'test1'});
    const router = createRouter(childRoute);
    const children = jest.fn(() => null);

    renderResourceTabs({children, route, router, titleProperty: 'test2'});

    await waitFor(() => {
        expect(children).toBeCalledWith({
            locales: undefined,
            resourceStore: expect.anything(),
            title: 'value2',
        });
    });
});

test('renders loader if resource store is not initialized', () => {
    mockResourceStore({initialized: false});

    const childRoute = {name: 'route1', options: {tabTitle: 'tabTitle1'}};
    const route = createRoute([childRoute]);
    const router = createRouter(childRoute);

    const {queryByTestId, asFragment} = renderResourceTabs({
        children: () => (<h1>Child</h1>),
        route,
        router,
    });

    expect(document.querySelector('.spinner')).toBeInTheDocument();
    expect(queryByTestId('tabs')).not.toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('renders tabs and child if resource store is initialized', () => {
    mockResourceStore();

    const childRoute = {name: 'route1', options: {tabTitle: 'tabTitle1'}};
    const route = createRoute([childRoute]);
    const router = createRouter(childRoute);

    const {getByTestId, getByText, asFragment} = renderResourceTabs({
        children: () => (<h1>Child</h1>),
        route,
        router,
    });

    expect(getByTestId('tabs')).toBeInTheDocument();
    expect(getByText('Child')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('passes sorted and visible routeChildren to tabs', () => {
    mockResourceStore({data: {test: 1}});

    const childRoute1 = {name: 'route1', options: {tabOrder: 40, tabTitle: 'tabTitle1'}};
    const childRoute2 = {name: 'route2', options: {tabCondition: 'test == 1', tabOrder: -10, tabTitle: 'tabTitle2'}};
    const childRoute3 = {name: 'route3', options: {tabCondition: 'test == 2', tabOrder: 10, tabTitle: 'tabTitle3'}};
    const route = createRoute([childRoute1, childRoute2, childRoute3]);
    const router = createRouter(childRoute2);

    const Child = ({route: childRoute}) => (<h1>{childRoute.name}</h1>);

    renderResourceTabs({
        children: () => (<Child route={childRoute2} />),
        route,
        router,
    });

    expect(getTabsProps().routeChildren).toEqual([childRoute2, childRoute1]);
});

test('updates visible tabs when tab condition changes', async() => {
    const data = observable({test: 1});
    mockResourceStore({data});

    const childRoute1 = {name: 'route1', options: {tabCondition: 'test == 1', tabTitle: 'tabTitle1'}};
    const childRoute2 = {name: 'route2', options: {tabCondition: 'test == 2', tabTitle: 'tabTitle2'}};
    const route = createRoute([childRoute1, childRoute2]);
    const router = createRouter(childRoute1);

    render(
        <ResourceTabs
            route={route}
            router={router}
        >
            {() => null}
        </ResourceTabs>
    );

    expect(getTabsProps().routeChildren).toEqual([childRoute1]);

    act(() => {
        data.test = 2;
    });

    await waitFor(() => {
        expect(getTabsProps().routeChildren).toEqual([childRoute2]);
    });
});

test('passes selectedIndex based on child route in visible tabs', () => {
    mockResourceStore();

    const childRoute1 = {name: 'route1', options: {tabTitle: 'tabTitle1'}};
    const childRoute2 = {name: 'route2', options: {tabTitle: 'tabTitle2'}};
    const route = createRoute([childRoute1, childRoute2]);
    const router = createRouter(childRoute2);

    const Child = ({route: childRoute}) => (<h1>{childRoute.name}</h1>);

    renderResourceTabs({
        children: () => (<Child route={childRoute2} />),
        route,
        router,
    });

    expect(getTabsProps().selectedIndex).toEqual(1);
});

test('creates resource store on mount and destroys it on unmount', () => {
    mockResourceStore();

    const route = createRoute([]);
    const router = createRouter(route, {id: 5});

    const {unmount} = renderResourceTabs({route, router});

    expect(ResourceStoreMock).toBeCalledWith('test', 5, {});

    unmount();

    expect(ResourceStoreMock.mock.instances[0].destroy).toBeCalled();
});

test('creates resource store with locale binding if locales are configured', () => {
    mockResourceStore();

    const route = createRoute([], {locales: ['de', 'en']});
    const router = createRouter(route, {id: 5});

    const {unmount} = renderResourceTabs({route, router});

    expect(router.bind).toBeCalledWith('locale', expect.anything());
    expect(getMockCallArg(ResourceStoreMock, 0, 2).locale).toBeDefined();

    unmount();
});

test('prefers locales from route over locales prop for child function', async() => {
    mockResourceStore();

    const routeLocales = observable(['de', 'en']);
    const route = createRoute([], {locales: routeLocales});
    const router = createRouter(route, {id: 5});
    const children = jest.fn(() => null);

    renderResourceTabs({
        children,
        locales: ['fr', 'nl'],
        route,
        router,
    });

    await waitFor(() => {
        expect(children).toBeCalledWith({
            locales: routeLocales,
            resourceStore: expect.anything(),
            title: undefined,
        });
    });
});

test('creates new resource store if resourceKey changes', async() => {
    mockResourceStore();

    const initialRoute = createRoute([], {resourceKey: 'snippets'});
    const router = createRouter(initialRoute, {id: 5});

    const {rerender} = render(
        <ResourceTabs route={initialRoute} router={router}>{() => null}</ResourceTabs>
    );

    const nextRoute = createRoute([], {resourceKey: 'contacts'});

    rerender(<ResourceTabs route={nextRoute} router={router}>{() => null}</ResourceTabs>);

    await waitFor(() => {
        expect(ResourceStoreMock).toHaveBeenLastCalledWith('contacts', 5, {});
    });
    expect(ResourceStoreMock.mock.instances[0].destroy).toBeCalled();
});

test('does not create new resource store if route changes outside of tabs', async() => {
    mockResourceStore();

    const initialRoute = createRoute([], {resourceKey: 'snippets'});
    const router = createRouter(initialRoute, {id: 5});

    const {rerender} = render(
        <ResourceTabs route={initialRoute} router={router}>{() => null}</ResourceTabs>
    );

    const nextRoute = createRoute([], {resourceKey: 'contacts'});

    act(() => {
        router._updateHooks[0](nextRoute);
    });

    rerender(<ResourceTabs route={nextRoute} router={router}>{() => null}</ResourceTabs>);

    await waitFor(() => {
        expect(ResourceStoreMock).toHaveBeenCalledTimes(1);
    });
});

test('creates new resource store if id changes', async() => {
    mockResourceStore();

    const route = createRoute([], {resourceKey: 'snippets'});
    const router = createRouter(route, {id: 5});

    const {rerender} = render(
        <ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>
    );

    router.attributes = {id: 6};

    rerender(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);

    await waitFor(() => {
        expect(ResourceStoreMock).toHaveBeenLastCalledWith('snippets', 6, {});
    });
    expect(ResourceStoreMock.mock.instances[0].destroy).toBeCalled();
});

test('reload hook reloads resourceStore when route changes to child or parent route', () => {
    mockResourceStore();

    const childRoute1 = {name: 'route1', options: {}};
    const childRoute2 = {name: 'route2', options: {}};
    const route = createRoute([childRoute1, childRoute2]);
    const router = createRouter(childRoute2, {foo: 'bar'});

    render(
        <ResourceTabs
            route={route}
            router={router}
        >
            {() => null}
        </ResourceTabs>
    );

    const reloadHook = router._updateHooks[1];

    act(() => {
        reloadHook(childRoute1, {foo: 'bar'});
    });
    expect(ResourceStoreMock.mock.instances[0].reload).toBeCalledTimes(1);

    act(() => {
        reloadHook(route);
    });
    expect(ResourceStoreMock.mock.instances[0].reload).toBeCalledTimes(2);
});

test('reload hook does not reload resourceStore for same route, external route, or changed id', () => {
    mockResourceStore();

    const childRoute = {name: 'route1', options: {}};
    const route = createRoute([childRoute]);
    const router = createRouter(childRoute, {id: 1});

    render(
        <ResourceTabs
            route={route}
            router={router}
        >
            {() => null}
        </ResourceTabs>
    );

    const reloadHook = router._updateHooks[1];

    act(() => {
        reloadHook(childRoute);
        reloadHook({name: 'external'}, {id: 1});
        reloadHook(childRoute, {id: 2});
    });

    expect(ResourceStoreMock.mock.instances[0].reload).not.toBeCalled();
});
