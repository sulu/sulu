/* eslint-disable flowtype/require-valid-file-annotation */
import {extendObservable, extendObservable as mockExtendObservable, observable} from 'mobx';
import React from 'react';
import {act, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceTabs from '../ResourceTabs';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import {createTestRef, mockResourceStoreImplementation} from '../../../utils/TestHelper';

jest.mock('debounce', () => jest.fn((callback) => callback));
jest.mock('../../../components/Loader', () => () => 'loader');

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

jest.mock('../../../utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'tabTitle1':
                return 'Tab Titel 1';
            case 'tabTitle2':
                return 'Tab Titel 2';
            case 'tabTitle3':
                return 'Tab Titel 3';
            case 'tabTitle4':
                return 'Tab Titel 4';
        }
    },
}));

jest.mock('../../../services/Router', () => jest.fn(function() {
    this.addUpdateRouteHook = jest.fn(function() {
        return jest.fn();
    });

    this.bind = jest.fn();
    this.navigate = jest.fn();
    this.redirect = jest.fn();
    this.route = undefined;

    mockExtendObservable(this, {attributes: {}});
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn());

beforeEach(() => {
    ResourceStore.mockReset();
});

function mockResourceStore(implementation) {
    mockResourceStoreImplementation(ResourceStore, implementation);
}

function createResourceTabsRef() {
    return createTestRef();
}

function getTabLabels() {
    return screen.getAllByRole('button').map((button) => button.textContent);
}

function waitForAutorun() {
    return new Promise((resolve) => setTimeout(resolve));
}

test('Should pass the tab title from the ResourceStore as configured in the route', async() => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const route = {
        options: {
            resourceKey: 'test',
            titleProperty: 'test1',
        },
        children: [
            {
                name: 'Tab 1',
                options: {
                    tabTitle: 'tabTitle1',
                },
            },
            {
                name: 'Tab 2',
                options: {
                    tabTitle: 'tabTitle2',
                },
            },
        ],
    };

    const router = new Router();
    router.attributes = {id: 1};
    router.route = route.children[1];

    const children = jest.fn();
    const ref = createResourceTabsRef();
    render(<ResourceTabs ref={ref} route={route} router={router}>{children}</ResourceTabs>);

    act(() => {
        ref.current.resourceStore.data = {test1: 'value1'};
    });

    await waitFor(() => expect(children).toHaveBeenLastCalledWith(
        {locales: undefined, resourceStore: expect.anything(ResourceStore), title: 'value1'}
    ));
});

test('Should not pass the tab title from the ResourceStore if no titleProperty is set', async() => {
    mockResourceStore(function() {
        this.initialized = true;
        this.loading = false;
        this.load = jest.fn();
        extendObservable(this, {data: {test1: 'value1', test2: 'value2'}});
    });

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            {
                name: 'Tab 1',
                options: {
                    tabTitle: 'tabTitle1',
                },
            },
            {
                name: 'Tab 2',
                options: {
                    tabTitle: 'tabTitle2',
                },
            },
        ],
    };

    const router = new Router();
    router.attributes = {id: 1};
    router.route = route.children[1];

    const children = jest.fn();

    const ref = createResourceTabsRef();
    render(<ResourceTabs ref={ref} route={route} router={router}>{children}</ResourceTabs>);

    act(() => {
        ref.current.resourceStore.data = {test1: 'value1'};
    });

    await waitFor(() => expect(children).toHaveBeenLastCalledWith(
        {locales: undefined, resourceStore: expect.anything(ResourceStore), title: undefined}
    ));
});

test('Should pass the tab title from the resourceStore as configured in the props to the child component', () => {
    mockResourceStore(function() {
        this.initialized = true;
        this.loading = false;
        this.load = jest.fn();
        extendObservable(this, {data: {test1: 'value1', test2: 'value2'}});
    });

    const route = {
        options: {
            resourceKey: 'test',
            titleProperty: 'test1',
        },
        children: [
            {
                name: 'Tab 1',
                options: {
                    tabTitle: 'tabTitle1',
                },
            },
            {
                name: 'Tab 2',
                options: {
                    tabTitle: 'tabTitle2',
                },
            },
        ],
    };

    const router = new Router();
    router.attributes = {id: 1};
    router.route = route.children[1];

    const children = jest.fn();

    render(
        <ResourceTabs route={route} router={router} titleProperty="test2">
            {children}
        </ResourceTabs>
    );

    expect(children).toHaveBeenCalledWith({
        locales: undefined,
        resourceStore: expect.any(ResourceStore),
        title: 'value2',
    });
});

test('Should not render the tab title on the first tab when tabOrder is defined', async() => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const route = {
        options: {
            resourceKey: 'test',
            titleProperty: 'test1',
        },
        children: [
            {
                name: 'Tab 2',
                options: {
                    tabOrder: 2,
                    tabTitle: 'tabTitle2',
                },
            },
            {
                name: 'Tab 1',
                options: {
                    tabOrder: 1,
                    tabTitle: 'tabTitle1',
                },
            },
        ],
    };

    const router = new Router();
    router.attributes = {id: 1};
    router.route = route.children[1];

    const Child = () => (<h1>Child</h1>);

    const ref = createResourceTabsRef();
    render(
        <ResourceTabs ref={ref} route={route} router={router}>
            {() => (<Child route={route.children[1]} />)}
        </ResourceTabs>
    );

    act(() => {
        ref.current.resourceStore.data = {test1: 'value1'};
    });

    expect(await screen.findByRole('heading', {name: 'Child'})).toBeInTheDocument();

    expect(screen.queryByRole('heading', {name: 'value1'})).not.toBeInTheDocument();
});

test('Should render the child components after the tabs', () => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            {
                name: 'Tab 1',
                options: {
                    tabTitle: 'tabTitle1',
                },
            },
            {
                name: 'Tab 2',
                options: {
                    tabTitle: 'tabTitle2',
                },
            },
        ],
    };

    const router = new Router();
    router.attributes = {id: 1};
    router.route = route.children[0];

    const Child = () => (<h1>Child</h1>);

    render(
        <ResourceTabs isRootView={true} route={route} router={router}>{() => (<Child />)}</ResourceTabs>
    );

    expect(getTabLabels()).toEqual(['Tab Titel 1', 'Tab Titel 2']);
    expect(screen.getByRole('heading', {name: 'Child'})).toBeInTheDocument();
});

test('Should render a loader if resourceStore was not initialized yet', () => {
    mockResourceStore(function() {
        this.initialized = false;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            {
                name: 'Tab 1',
                options: {
                    tabTitle: 'tabTitle1',
                },
            },
            {
                name: 'Tab 2',
                options: {
                    tabTitle: 'tabTitle2',
                },
            },
        ],
    };

    const router = new Router();
    router.attributes = {id: 1};
    router.route = route.children[0];

    const Child = () => (<h1>Child</h1>);

    render(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    expect(screen.getByText('loader')).toBeInTheDocument();
    expect(screen.queryByRole('heading', {name: 'Child'})).not.toBeInTheDocument();
});

test('Should mark the currently active child route as selected tab', () => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'Tab 1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'Tab 2',
        options: {
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const router = new Router();
    router.attributes = {id: 1};
    router.route = route.children[1];

    const Child = () => (<h1>Child</h1>);

    render(
        <ResourceTabs route={route} router={router}>{() => (<Child route={route.children[1]} />)}</ResourceTabs>
    );

    expect(getTabLabels()).toEqual(['Tab Titel 1', 'Tab Titel 2']);
    expect(screen.getByRole('button', {name: 'Tab Titel 1'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'Tab Titel 2'})).toBeDisabled();
    expect(screen.getByRole('heading', {name: 'Child'})).toBeInTheDocument();
});

test('Should consider the tabOrder option of the route', () => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'Tab 1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'Tab 2',
        options: {
            tabOrder: 40,
            tabTitle: 'tabTitle2',
        },
    };
    const childRoute3 = {
        name: 'Tab 3',
        options: {
            tabTitle: 'tabTitle3',
        },
    };
    const childRoute4 = {
        name: 'Tab 4',
        options: {
            tabOrder: -10,
            tabTitle: 'tabTitle4',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
            childRoute3,
            childRoute4,
        ],
    };

    const router = new Router();
    router.attributes = {id: 1};
    router.route = route.children[1];

    const Child = () => (<h1>Child</h1>);

    render(
        <ResourceTabs route={route} router={router}>{() => (<Child route={route.children[1]} />)}</ResourceTabs>
    );

    expect(getTabLabels()).toEqual(['Tab Titel 4', 'Tab Titel 1', 'Tab Titel 3', 'Tab Titel 2']);
});

test('Should hide tabs which do not match the tab condition', async() => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'Tab 1',
        options: {
            tabCondition: 'test == 1',
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'Tab 2',
        options: {
            tabCondition: 'test == 2',
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const router = new Router();
    router.attributes = {id: 1};
    router.route = route.children[1];

    const Child = () => (<h1>Child</h1>);

    const ref = createResourceTabsRef();
    render(
        <ResourceTabs isRootView={true} ref={ref} route={route} router={router}>
            {() => (<Child route={route.children[1]} />)}
        </ResourceTabs>
    );

    act(() => {
        ref.current.resourceStore.data = {test: 1};
    });

    await waitFor(() => expect(getTabLabels()).toEqual(['Tab Titel 1']));

    act(() => {
        ref.current.resourceStore.data.test = 2;
    });

    await waitFor(() => expect(getTabLabels()).toEqual(['Tab Titel 2']));
});

test('Should redirect to first child route if no tab is active by default', async() => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = route;

    const Child = () => (<h1>Child</h1>);
    render(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    await waitFor(() => expect(router.redirect).toHaveBeenCalledWith('route1', attributes));
});

test('Should redirect to first visible child route if no tab is active', async() => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabCondition: 'test == 1',
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabCondition: 'test == 2',
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = route;

    const Child = () => (<h1>Child</h1>);
    const ref = createResourceTabsRef();
    render(<ResourceTabs ref={ref} route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    act(() => {
        ref.current.resourceStore.data = {test: 2};
    });

    await waitFor(() => expect(router.redirect).toHaveBeenCalledWith('route2', attributes));
});

test('Should redirect to first visible child route if invisible tab is active', async() => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabCondition: 'test == 1',
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabCondition: 'test == 2',
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = childRoute1;

    const Child = () => (<h1>Child</h1>);
    const ref = createResourceTabsRef();
    render(<ResourceTabs ref={ref} route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    act(() => {
        ref.current.resourceStore.data = {test: 2};
    });

    await waitFor(() => expect(router.redirect).toHaveBeenCalledWith('route2', attributes));
});

test('Should redirect to highest prioritized tab if no tab is active', async() => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
            tabPriority: 100,
        },
    };
    const childRoute3 = {
        name: 'route3',
        options: {
            tabTitle: 'tabTitle3',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
            childRoute3,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = route;

    const Child = () => (<h1>Child</h1>);
    render(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    await waitFor(() => expect(router.redirect).toHaveBeenCalledWith('route2', attributes));
});

test('Should not redirect to first child route if resourceStore is not initialized', async() => {
    mockResourceStore(function() {
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = route;

    const Child = () => (<h1>Child</h1>);
    render(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    await waitForAutorun();

    expect(router.redirect).not.toHaveBeenCalledWith('route1', attributes);
});

test('Should not redirect to first child route if resourceStore is currently loading', async() => {
    mockResourceStore(function() {
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = route;

    const Child = () => (<h1>Child</h1>);
    render(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    await waitForAutorun();

    expect(router.redirect).not.toHaveBeenCalledWith('route1', attributes);
});

test('Should not redirect if a tab is already active', () => {
    mockResourceStore(function() {
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = childRoute1;

    const Child = () => (<h1>Child</h1>);
    render(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    expect(router.redirect).not.toHaveBeenCalled();
});

test('Should reload ResourceStore if route is about to change to another child route', () => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        this.reload = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {},
    };
    const childRoute2 = {
        name: 'route2',
        options: {},
    };
    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        attribute: 'value',
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = childRoute2;

    const Child = () => (<h1>Child</h1>);
    const ref = createResourceTabsRef();
    render(<ResourceTabs ref={ref} route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    router.addUpdateRouteHook.mock.calls[1][0](childRoute1, attributes);

    expect(ref.current.resourceStore.reload).toHaveBeenCalledWith();
});

test('Should not reload ResourceStore if route is about to change to same route', () => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        this.reload = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {},
    };
    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
        ],
    };

    const attributes = {
        attribute: 'value',
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = childRoute1;

    const Child = () => (<h1>Child</h1>);
    const ref = createResourceTabsRef();
    render(<ResourceTabs ref={ref} route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    router.addUpdateRouteHook.mock.calls[0][0](childRoute1);

    expect(ref.current.resourceStore.reload).not.toHaveBeenCalled();
});

test('Should reload ResourceStore if route is about to change to parent route', () => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        this.reload = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {},
    };
    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
        ],
    };

    const attributes = {
        attribute: 'value',
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = childRoute1;

    const Child = () => (<h1>Child</h1>);
    const ref = createResourceTabsRef();
    render(<ResourceTabs ref={ref} route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    router.addUpdateRouteHook.mock.calls[1][0](route);

    expect(ref.current.resourceStore.reload).toHaveBeenCalledWith();
});

test('Should not reload ResourceStore if route is about to change to route outside of tabs', () => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        this.reload = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {},
    };
    const route1 = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
        ],
    };
    const route2 = {};

    const attributes = {
        attribute: 'value',
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = childRoute1;

    const Child = () => (<h1>Child</h1>);
    const ref = createResourceTabsRef();
    render(<ResourceTabs ref={ref} route={route1} router={router}>{() => (<Child />)}</ResourceTabs>);

    router.addUpdateRouteHook.mock.calls[0][0](route2);

    expect(ref.current.resourceStore.reload).not.toHaveBeenCalledWith();
});

test('Should navigate to child route if tab is clicked', async() => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {},
    };
    const childRoute2 = {
        name: 'route2',
        options: {},
    };
    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        attribute: 'value',
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = childRoute1;

    const Child = () => (<h1>Child</h1>);
    const user = userEvent.setup();
    render(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    await user.click(screen.getByRole('button', {name: 'route2'}));

    expect(router.navigate).toHaveBeenCalledWith('route2', attributes);
});

test('Should navigate to child route if tab is clicked with hidden tabs', async() => {
    mockResourceStore(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabCondition: 'test == 2',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {},
    };
    const childRoute3 = {
        name: 'route3',
        options: {},
    };
    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
            childRoute3,
        ],
    };

    const attributes = {
        attribute: 'value',
    };

    const router = new Router();
    router.attributes = attributes;
    router.route = childRoute1;

    const Child = () => (<h1>Child</h1>);
    const ref = createResourceTabsRef();
    const user = userEvent.setup();
    render(<ResourceTabs ref={ref} route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    act(() => {
        ref.current.resourceStore.data = {test: 1};
    });

    await waitFor(() => expect(getTabLabels()).toEqual(['route2', 'route3']));

    await user.click(screen.getByRole('button', {name: 'route2'}));

    expect(router.navigate).toHaveBeenCalledWith('route2', attributes);
});

test('Should create a ResourceStore on mount and destroy it on unmount', () => {
    mockResourceStore(function() {
        this.destroy = jest.fn();
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
        },
    };

    const router = new Router();
    router.attributes = {id: 5};
    router.route = route;

    router.addUpdateRouteHook.mockImplementationOnce(() => jest.fn());
    const {unmount} = render(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);
    const resourceStoreConstructorCall = ResourceStore.mock.calls;
    expect(resourceStoreConstructorCall[0][0]).toEqual('snippets');
    expect(resourceStoreConstructorCall[0][1]).toEqual(5);
    expect(resourceStoreConstructorCall[0][2].locale).not.toBeDefined();

    unmount();
    expect(ResourceStore.mock.instances[0].destroy).toHaveBeenCalled();
});

test('Should create a new ResourceStore if the resourceKey changes', () => {
    mockResourceStore(function() {
        this.destroy = jest.fn();
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
        },
    };

    const router = new Router();
    router.attributes = {id: 5};
    router.route = route;

    const {rerender, unmount} = render(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);

    expect(ResourceStore).toHaveBeenLastCalledWith('snippets', 5, {});

    const newRoute = {
        children: [],
        options: {
            resourceKey: 'contacts',
        },
    };

    rerender(<ResourceTabs route={newRoute} router={router}>{() => null}</ResourceTabs>);

    expect(ResourceStore.mock.instances[0].destroy).toHaveBeenCalled();
    expect(ResourceStore).toHaveBeenLastCalledWith('contacts', 5, {});

    unmount();
});

test('Should not create a new ResourceStore if the resourceKey changes but the router changes to another view', () => {
    mockResourceStore(function() {
        this.destroy = jest.fn();
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
        },
    };

    const router = new Router();
    router.attributes = {id: 5};

    const updateRouteHooks = [];
    router.addUpdateRouteHook.mockImplementationOnce((updateRouteHook) => {
        updateRouteHooks.push(updateRouteHook);
        return jest.fn();
    });

    const {rerender, unmount} = render(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);

    expect(ResourceStore).toHaveBeenLastCalledWith('snippets', 5, {});

    const newRoute = {
        children: [],
        options: {
            resourceKey: 'contacts',
        },
    };

    updateRouteHooks.forEach((updateRouteHook) => updateRouteHook(newRoute));

    rerender(<ResourceTabs route={newRoute} router={router}>{() => null}</ResourceTabs>);

    expect(ResourceStore).not.toHaveBeenCalledWith('contacts', 5, {});

    unmount();
});

test('Should create a new ResourceStore if the ID changes', () => {
    mockResourceStore(function() {
        this.destroy = jest.fn();
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
        },
    };

    const router = new Router();
    router.route = route;
    router.attributes = {id: 5};

    router.addUpdateRouteHook.mockImplementationOnce(() => jest.fn());
    const {unmount} = render(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);

    expect(ResourceStore).toHaveBeenLastCalledWith('snippets', 5, {});

    act(() => {
        router.attributes = {
            id: 6,
        };
    });

    expect(ResourceStore.mock.instances[0].destroy).toHaveBeenCalled();
    expect(ResourceStore).toHaveBeenLastCalledWith('snippets', 6, {});

    unmount();
});

test('Should create a ResourceStore with locale on mount if locales have been passed in route options', () => {
    mockResourceStore(function() {
        this.destroy = jest.fn();
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales: ['de', 'en'],
        },
    };

    const router = new Router();
    router.attributes = {id: 5};
    router.route = route;

    const {unmount} = render(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);
    const resourceStoreConstructorCall = ResourceStore.mock.calls;
    expect(resourceStoreConstructorCall[0][0]).toEqual('snippets');
    expect(resourceStoreConstructorCall[0][1]).toEqual(5);
    expect(resourceStoreConstructorCall[0][2].locale).toBeDefined();

    unmount();
    expect(ResourceStore.mock.instances[0].destroy).toHaveBeenCalled();
});

test('Should create a ResourceStore with locale on mount if locales have been passed as observable array', () => {
    mockResourceStore(function() {
        this.destroy = jest.fn();
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales: observable(['de', 'en']),
        },
    };

    const router = new Router();
    router.attributes = {id: 5};
    router.route = route;

    const {unmount} = render(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);
    const resourceStoreConstructorCall = ResourceStore.mock.calls;
    expect(router.bind).toHaveBeenCalled();
    expect(resourceStoreConstructorCall[0][0]).toEqual('snippets');
    expect(resourceStoreConstructorCall[0][1]).toEqual(5);
    expect(resourceStoreConstructorCall[0][2].locale).toBeDefined();

    unmount();
    expect(ResourceStore.mock.instances[0].destroy).toHaveBeenCalled();
});

test('Should pass the ResourceStore and locales to child components', () => {
    mockResourceStore(function() {
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const locales = observable(['de', 'en']);
    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales,
        },
    };

    const router = new Router();
    router.attributes = {id: 5};
    router.route = route;

    const ChildComponent = jest.fn(() => null);
    const ref = createResourceTabsRef();
    render(
        <ResourceTabs
            locales={[]}
            ref={ref}
            route={route}
            router={router}
        >
            {(props) => (<ChildComponent {...props} />)}
        </ResourceTabs>
    );

    expect(ChildComponent.mock.calls[0][0].resourceStore).toBe(ref.current.resourceStore);
    expect(ChildComponent.mock.calls[0][0].locales).toBe(locales);
});

test('Should pass locales from route options instead of props to child components', () => {
    mockResourceStore(function() {
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales: ['de', 'en'],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(() => jest.fn()),
        attributes: {
            id: 5,
        },
        bind: jest.fn(),
        route,
    };

    const ChildComponent = jest.fn(() => null);
    const ref = createResourceTabsRef();
    render(
        <ResourceTabs locales={['fr', 'nl']} ref={ref} route={route} router={router}>
            {(props) => (<ChildComponent {...props} />)}
        </ResourceTabs>
    );

    expect(ChildComponent.mock.calls[0][0].resourceStore).toBe(ref.current.resourceStore);
    expect(ChildComponent.mock.calls[0][0].locales).toEqual(['de', 'en']);
});
