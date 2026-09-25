// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Router, {Route} from '../../../services/Router';
import Tabs from '../Tabs';
import Requester from '../../../services/Requester';

jest.mock('../../../services/Requester', () => ({
    get: jest.fn(),
}));
Requester.handleResponseHooks = [];

jest.mock('debounce', () => jest.fn((callback) => callback));

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

jest.mock('../../../services/Router/Router', () => jest.fn());

jest.mock('../../../utils/Translator');

test('Should render the children after the tabs', () => {
    const childRoute1 = new Route({
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        path: '/route1',
        type: 'route1',
    });
    const childRoute2 = new Route({
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
        path: '/route2',
        type: 'route1',
    });

    const route = new Route({
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        path: '/parent',
        type: 'route1',
    });

    route.children.push(childRoute1);
    route.children.push(childRoute2);

    const attributes = {
        id: 1,
    };

    // $FlowFixMe
    Router.mockImplementation(function() {
        this.attributes = attributes;
        this.redirect = jest.fn();
        this.route = route;
    });

    const router = new Router({});

    const Child = () => (<h1>Child</h1>);
    render(<Tabs isRootView={true} route={route} router={router}>{() => (<Child />)}</Tabs>);

    expect(screen.getByRole('button', {name: 'tabTitle1'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'tabTitle2'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'Child'})).toBeInTheDocument();
});

test('Should render the tab badges', async() => {
    const promise = Promise.resolve({count: 2});
    Requester.get.mockReturnValue(promise);
    Requester.handleResponseHooks = [];

    const childRoute1 = new Route({
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
            tabBadges: [
                {
                    dataPath: '/count',
                    requestParameters: {
                        foo: 'bar',
                        bar: 'baz',
                    },
                    routeName: 'app.notification_count',
                    routerAttributesToRequest: {
                        locale: 'locale',
                        id: 'entityId',
                    },
                    visibleCondition: 'value != 0',
                },
            ],
        },
        path: '/route1',
        type: 'route1',
    });

    const route = new Route({
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        path: '/parent',
        type: 'route1',
    });

    route.children.push(childRoute1);

    const attributes = {
        id: 1,
    };

    // $FlowFixMe
    Router.mockImplementation(function() {
        this.attributes = attributes;
        this.redirect = jest.fn();
        this.route = route;
    });

    const router = new Router({});

    const Child = () => (<h1>Child</h1>);
    render(<Tabs isRootView={true} route={route} router={router}>{() => (<Child />)}</Tabs>);

    await promise;

    expect(await screen.findByText('2')).toBeInTheDocument();
});

test('Should render the header between children and tabs', () => {
    const childRoute1 = new Route({
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        path: '/route1',
        type: 'route1',
    });

    const route = new Route({
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        path: '/parent',
        type: 'route1',
    });

    route.children.push(childRoute1);

    const attributes = {
        id: 1,
    };

    // $FlowFixMe
    Router.mockImplementation(function() {
        this.attributes = attributes;
        this.redirect = jest.fn();
        this.route = route;
    });

    const router = new Router({});

    const Child = () => (<h2>Child</h2>);
    render(
        <Tabs
            header={<h1>Header</h1>}
            isRootView={true}
            route={route}
            router={router}
        >
            {() => (<Child />)}
        </Tabs>
    );

    expect(screen.getByRole('heading', {name: 'Header'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'Child'})).toBeInTheDocument();
});

test('Should render the children with the passed props', () => {
    const childRoute1 = new Route({
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        path: '/route1',
        type: 'route1',
    });

    const route = new Route({
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        path: '/parent',
        type: 'route1',
    });

    route.children.push(childRoute1);

    const attributes = {
        id: 1,
    };

    // $FlowFixMe
    Router.mockImplementation(function() {
        this.attributes = attributes;
        this.redirect = jest.fn();
        this.route = route;
    });

    const router = new Router({});

    const Child = ({test}) => (<h2>{test}</h2>);
    render(
        <Tabs
            childrenProps={{test: 'Value'}}
            isRootView={true}
            route={route}
            router={router}
        >
            {(props) => (<Child {...props} />)}
        </Tabs>
    );

    expect(screen.getByRole('heading', {name: 'Value'})).toBeInTheDocument();
});

test('Should render the active child with disabledTabGap option', () => {
    const childRoute1 = new Route({
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
            disableTabGap: true,
        },
        path: '/route1',
        type: 'route1',
    });
    const childRoute2 = new Route({
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
        path: '/route2',
        type: 'route1',
    });

    const route = new Route({
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        path: '/parent',
        type: 'route1',
    });

    route.children.push(childRoute1);
    route.children.push(childRoute2);

    const attributes = {
        id: 1,
    };

    const activeRoute = route.children[1];

    // $FlowFixMe
    Router.mockImplementation(function() {
        this.attributes = attributes;
        this.redirect = jest.fn();
        this.route = activeRoute;
    });

    const router = new Router({});

    const Child = () => (<h1>Child</h1>);
    render(
        <Tabs
            route={route}
            router={router}
            selectedIndex={0}
        >
            {() => (<Child route={activeRoute} />)}
        </Tabs>
    );

    expect(screen.getByRole('button', {name: 'tabTitle1'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'tabTitle2'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'Child'})).toBeInTheDocument();
});

test('Should consider the tabOrder when rendering the tabs', () => {
    const childRoute1 = new Route({
        name: 'route1',
        options: {
            tabOrder: 40,
            tabTitle: 'tabTitle1',
        },
        path: '/route1',
        type: 'route1',
    });
    const childRoute2 = new Route({
        name: 'route2',
        options: {
            tabOrder: 30,
            tabTitle: 'tabTitle2',
        },
        path: '/route2',
        type: 'route2',
    });
    const childRoute3 = new Route({
        name: 'route3',
        options: {
            tabOrder: 50,
            tabTitle: 'tabTitle3',
        },
        path: '/route3',
        type: 'route3',
    });

    const route = new Route({
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        path: '/parent',
        type: 'route1',
    });

    route.children.push(childRoute1);
    route.children.push(childRoute2);
    route.children.push(childRoute3);

    const attributes = {
        id: 1,
    };

    // $FlowFixMe
    Router.mockImplementation(function() {
        this.attributes = attributes;
        this.redirect = jest.fn();
        this.route = route;
    });

    const router = new Router({});

    const Child = () => (<h1>Child</h1>);
    render(<Tabs route={route} router={router}>{() => (<Child />)}</Tabs>);

    expect(screen.getAllByRole('button').map((button) => button.textContent)).toEqual([
        'tabTitle2',
        'tabTitle1',
        'tabTitle3',
    ]);
});

test('Should mark currently active tab as selected according to prop', async() => {
    const childRoute1 = new Route({
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        path: '/route1',
        type: 'route1',
    });
    const childRoute2 = new Route({
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
        path: '/route2',
        type: 'route1',
    });

    const route = new Route({
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        path: '/parent',
        type: 'route1',
    });

    route.children.push(childRoute1);
    route.children.push(childRoute2);

    const attributes = {
        id: 1,
    };

    const activeRoute = route.children[1];

    // $FlowFixMe
    Router.mockImplementation(function() {
        this.attributes = attributes;
        this.redirect = jest.fn();
        this.route = activeRoute;
    });

    const router = new Router({});

    const Child = () => (<h1>Child</h1>);
    render(
        <Tabs route={route} router={router} selectedIndex={0}>{() => (<Child route={activeRoute} />)}</Tabs>
    );

    await waitFor(() => expect(router.redirect).not.toHaveBeenCalled());
    expect(screen.getByRole('button', {name: 'tabTitle1'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'tabTitle2'})).toBeEnabled();
});

test('Should mark currently active tab as selected', async() => {
    const childRoute1 = new Route({
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        path: '/route1',
        type: 'route1',
    });
    const childRoute2 = new Route({
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
        path: '/route2',
        type: 'route1',
    });

    const route = new Route({
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        path: '/parent',
        type: 'route1',
    });

    route.children.push(childRoute1);
    route.children.push(childRoute2);

    const attributes = {
        id: 1,
    };

    const activeRoute = route.children[1];

    // $FlowFixMe
    Router.mockImplementation(function() {
        this.attributes = attributes;
        this.redirect = jest.fn();
        this.route = activeRoute;
    });

    const router = new Router({});

    const Child = () => (<h1>Child</h1>);
    render(
        <Tabs route={route} router={router}>{() => (<Child route={activeRoute} />)}</Tabs>
    );

    await waitFor(() => expect(router.redirect).not.toHaveBeenCalled());
    expect(screen.getByRole('button', {name: 'tabTitle1'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'tabTitle2'})).toBeDisabled();
});

test('Should redirect to child route with highest priority if no tab is active by default', async() => {
    const childRoute1 = new Route({
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        path: '/route1',
        type: 'route1',
    });
    const childRoute2 = new Route({
        name: 'route2',
        options: {
            tabPriority: 100,
            tabTitle: 'tabTitle2',
        },
        path: '/route2',
        type: 'route1',
    });

    const route = new Route({
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        path: '/parent',
        type: 'route1',
    });

    route.children.push(childRoute1);
    route.children.push(childRoute2);

    const attributes = {
        id: 1,
    };

    // $FlowFixMe
    Router.mockImplementation(function() {
        this.attributes = attributes;
        this.redirect = jest.fn();
        this.route = route;
    });

    const router = new Router({});

    const Child = () => (<h1>Child</h1>);
    render(<Tabs route={route} router={router}>{() => (<Child />)}</Tabs>);

    await waitFor(() => expect(router.redirect).toHaveBeenCalledWith('route2', attributes));
});

test('Should redirect to child route from props with highest priority if no tab is active by default', async() => {
    const childRoute1 = new Route({
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        path: '/route1',
        type: 'route1',
    });
    const childRoute2 = new Route({
        name: 'route2',
        options: {
            tabPriority: 100,
            tabTitle: 'tabTitle2',
        },
        path: '/route2',
        type: 'route1',
    });

    const childRoutes = [childRoute1, childRoute2];

    const route = new Route({
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        path: '/parent',
        type: 'route1',
    });

    route.children.push(childRoute1, childRoute2);
    route.children.push(childRoute2);

    const attributes = {
        id: 1,
    };

    // $FlowFixMe
    Router.mockImplementation(function() {
        this.attributes = attributes;
        this.redirect = jest.fn();
        this.route = route;
    });

    const router = new Router({});

    const Child = () => (<h1>Child</h1>);
    render(<Tabs route={route} routeChildren={childRoutes} router={router}>{() => (<Child />)}</Tabs>);

    await waitFor(() => expect(router.redirect).toHaveBeenCalledWith('route2', attributes));
});

test('Navigate to tab with filtered attributes if it was clicked', async() => {
    const user = userEvent.setup();
    const childRoute1 = new Route({
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        path: '/route1',
        type: 'route1',
    });
    const childRoute2 = new Route({
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
        path: '/route2',
        type: 'route1',
    });

    const route = new Route({
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        path: '/parent',
        type: 'route1',
    });

    route.children.push(childRoute1);
    route.children.push(childRoute2);

    const attributes = {
        id: 1,
    };

    // $FlowFixMe
    Router.mockImplementation(function() {
        this.attributes = attributes;
        this.navigate = jest.fn();
        this.redirect = jest.fn();
        this.route = route;
    });

    const router = new Router({});

    const Child = () => (<h1>Child</h1>);
    render(<Tabs route={route} router={router}>{() => (<Child />)}</Tabs>);

    await user.click(screen.getByRole('button', {name: 'tabTitle2'}));
    expect(router.navigate).toHaveBeenCalledWith('route2', attributes);
});

test('Navigate to tab if it was clicked', async() => {
    const user = userEvent.setup();
    const childRoute1 = new Route({
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        path: '/route1',
        type: 'route1',
    });
    const childRoute2 = new Route({
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
        path: '/route2',
        type: 'route1',
    });

    const route = new Route({
        name: 'parent',
        options: {
            resourceKey: 'test',
            routerAttributesToBlacklist: ['sortColumn', 'sortOrder'],
        },
        path: '/parent',
        type: 'route1',
    });

    route.children.push(childRoute1);
    route.children.push(childRoute2);

    const attributes = {
        id: 1,
        sortColumn: 'size',
        sortOrder: 'asc',
    };

    // $FlowFixMe
    Router.mockImplementation(function() {
        this.attributes = attributes;
        this.navigate = jest.fn();
        this.redirect = jest.fn();
        this.route = route;
    });

    const router = new Router({});

    const Child = () => (<h1>Child</h1>);
    render(<Tabs route={route} router={router}>{() => (<Child />)}</Tabs>);

    await user.click(screen.getByRole('button', {name: 'tabTitle2'}));
    expect(router.navigate).toHaveBeenCalledWith('route2', {id: 1});
});
