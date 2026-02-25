// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable} from 'mobx';
import Router from '../../../services/Router';
import Tabs from '../Tabs';
import Badge from '../../../containers/Badge';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('debounce', () => jest.fn((callback) => callback));

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

jest.mock('../../../containers/Badge', () => jest.fn(function BadgeMock() {
    return <span data-testid="badge" />;
}));

jest.mock('../../../services/Router/Router', () => jest.fn(function() {
    this.addUpdateRouteHook = jest.fn(function() {
        return jest.fn();
    });

    this.bind = jest.fn();
    this.navigate = jest.fn();
    this.redirect = jest.fn();
    this.route = undefined;

    mockExtendObservable(this, {attributes: {}});
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const BadgeMock: any = Badge;

function createRoute(name: string, options: Object = {}): any {
    return {
        name,
        options,
    };
}

function createParentRoute(children: Array<any>, options: Object = {}): any {
    return {
        children,
        name: 'parent',
        options: {
            resourceKey: 'test',
            ...options,
        },
    };
}

function createRouter(route: any, attributes: Object = {id: 1}) {
    const router = new Router({});
    router.attributes = attributes;
    router.route = route;

    return router;
}

function renderTabs(props: Object = {}) {
    const defaultChildRoute = createRoute('route1', {tabTitle: 'tabTitle1'});
    const defaultRoute = createParentRoute([defaultChildRoute]);
    const defaultRouter = createRouter(defaultRoute);
    const view = render(
        <Tabs
            route={defaultRoute}
            router={defaultRouter}
            {...props}
        >
            {props.children || (() => null)}
        </Tabs>
    );

    return {
        route: defaultRoute,
        router: defaultRouter,
        ...view,
    };
}

function getRenderedTabTitles() {
    return screen
        .getAllByRole('button')
        .filter((button) => /^tabTitle/.test(button.textContent || ''))
        .map((button) => button.textContent);
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should render the children after the tabs', () => {
    const childRoute1 = createRoute('route1', {tabTitle: 'tabTitle1'});
    const childRoute2 = createRoute('route2', {tabTitle: 'tabTitle2'});
    const route = createParentRoute([childRoute1, childRoute2]);
    const router = createRouter(route);

    const Child = () => (<h1>Child</h1>);

    const {asFragment} = renderTabs({
        children: () => (<Child />),
        isRootView: true,
        route,
        router,
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the tab badges', () => {
    const childRoute1 = createRoute('route1', {
        tabBadges: [
            {
                dataPath: '/count',
                requestParameters: {foo: 'bar', bar: 'baz'},
                routeName: 'app.notification_count',
                routerAttributesToRequest: {locale: 'locale', id: 'entityId'},
                visibleCondition: 'value != 0',
            },
        ],
        tabTitle: 'tabTitle1',
    });
    const route = createParentRoute([childRoute1]);
    const router = createRouter(route);

    renderTabs({
        children: () => (<h1>Child</h1>),
        isRootView: true,
        route,
        router,
    });

    expect(BadgeMock).toBeCalledTimes(1);
    expect(getLatestMockProps(BadgeMock)).toEqual(expect.objectContaining({
        dataPath: '/count',
        requestParameters: {foo: 'bar', bar: 'baz'},
        routeName: 'app.notification_count',
        router,
    }));
});

test('Should render the header between children and tabs', () => {
    const childRoute1 = createRoute('route1', {tabTitle: 'tabTitle1'});
    const route = createParentRoute([childRoute1]);
    const router = createRouter(route);

    const Child = () => (<h2>Child</h2>);
    const {asFragment} = renderTabs({
        children: () => (<Child />),
        header: <h1>Header</h1>,
        isRootView: true,
        route,
        router,
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Should render the children with the passed props', () => {
    const childRoute1 = createRoute('route1', {tabTitle: 'tabTitle1'});
    const route = createParentRoute([childRoute1]);
    const router = createRouter(route);

    const Child = ({test}) => (<h2>{test}</h2>);

    const {asFragment} = renderTabs({
        children: (props) => (<Child {...props} />),
        childrenProps: {test: 'Value'},
        isRootView: true,
        route,
        router,
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Should consider the tabOrder when rendering the tabs', () => {
    const childRoute1 = createRoute('route1', {tabOrder: 40, tabTitle: 'tabTitle1'});
    const childRoute2 = createRoute('route2', {tabOrder: 30, tabTitle: 'tabTitle2'});
    const childRoute3 = createRoute('route3', {tabOrder: 50, tabTitle: 'tabTitle3'});
    const route = createParentRoute([childRoute1, childRoute2, childRoute3]);
    const router = createRouter(route);

    renderTabs({
        children: () => (<h1>Child</h1>),
        route,
        router,
    });

    expect(getRenderedTabTitles()).toEqual(['tabTitle2', 'tabTitle1', 'tabTitle3']);
});

test('Should mark currently active tab as selected according to selectedIndex prop', () => {
    const childRoute1 = createRoute('route1', {tabTitle: 'tabTitle1'});
    const childRoute2 = createRoute('route2', {tabTitle: 'tabTitle2'});
    const route = createParentRoute([childRoute1, childRoute2]);
    const router = createRouter(childRoute2);

    renderTabs({
        children: () => (<h1>Child</h1>),
        route,
        router,
        selectedIndex: 0,
    });

    expect(router.redirect).not.toBeCalled();
    expect(screen.getByRole('button', {name: 'tabTitle1'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'tabTitle2'})).toBeEnabled();
});

test('Should mark currently active tab as selected from child route', () => {
    const childRoute1 = createRoute('route1', {tabTitle: 'tabTitle1'});
    const childRoute2 = createRoute('route2', {tabTitle: 'tabTitle2'});
    const route = createParentRoute([childRoute1, childRoute2]);
    const router = createRouter(childRoute2);

    const Child = ({route: childRoute}) => (<h1>{childRoute.name}</h1>);

    renderTabs({
        children: () => (<Child route={childRoute2} />),
        route,
        router,
    });

    expect(router.redirect).not.toBeCalled();
    expect(screen.getByRole('button', {name: 'tabTitle2'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'tabTitle1'})).toBeEnabled();
});

test('Should redirect to child route with highest priority if no tab is active by default', () => {
    const childRoute1 = createRoute('route1', {tabTitle: 'tabTitle1'});
    const childRoute2 = createRoute('route2', {tabPriority: 100, tabTitle: 'tabTitle2'});
    const route = createParentRoute([childRoute1, childRoute2]);
    const attributes = {id: 1};
    const router = createRouter(route, attributes);

    renderTabs({
        children: () => (<h1>Child</h1>),
        route,
        router,
    });

    expect(router.redirect).toBeCalledWith('route2', attributes);
});

test('Should redirect to child route from routeChildren prop with highest priority if no tab is active', () => {
    const childRoute1 = createRoute('route1', {tabTitle: 'tabTitle1'});
    const childRoute2 = createRoute('route2', {tabPriority: 100, tabTitle: 'tabTitle2'});
    const childRoutes = [childRoute1, childRoute2];
    const route = createParentRoute([childRoute1, childRoute2]);
    const attributes = {id: 1};
    const router = createRouter(route, attributes);

    renderTabs({
        children: () => (<h1>Child</h1>),
        route,
        routeChildren: childRoutes,
        router,
    });

    expect(router.redirect).toBeCalledWith('route2', attributes);
});

test('Should not redirect if a tab is already active', () => {
    const childRoute1 = createRoute('route1', {tabTitle: 'tabTitle1'});
    const childRoute2 = createRoute('route2', {tabTitle: 'tabTitle2'});
    const route = createParentRoute([childRoute1, childRoute2]);
    const router = createRouter(childRoute1);

    renderTabs({
        children: () => (<h1>Child</h1>),
        route,
        router,
    });

    expect(router.redirect).not.toBeCalled();
});

test('Navigate to tab if it was clicked', async() => {
    const childRoute1 = createRoute('route1', {tabTitle: 'tabTitle1'});
    const childRoute2 = createRoute('route2', {tabTitle: 'tabTitle2'});
    const route = createParentRoute([childRoute1, childRoute2]);
    const attributes = {id: 1};
    const router = createRouter(route, attributes);
    const user = userEvent.setup();
    router.navigate = jest.fn();

    renderTabs({
        children: () => (<h1>Child</h1>),
        route,
        router,
    });

    await user.click(screen.getByRole('button', {name: 'tabTitle2'}));

    expect(router.navigate).toBeCalledWith('route2', attributes);
});

test('Should not pass blacklisted router attributes when tab is clicked', async() => {
    const childRoute1 = createRoute('route1', {tabTitle: 'tabTitle1'});
    const childRoute2 = createRoute('route2', {tabTitle: 'tabTitle2'});
    const route = createParentRoute([childRoute1, childRoute2], {
        routerAttributesToBlacklist: ['sortColumn', 'sortOrder'],
    });
    const attributes = {id: 1, sortColumn: 'size', sortOrder: 'asc'};
    const router = createRouter(route, attributes);
    const user = userEvent.setup();
    router.navigate = jest.fn();

    renderTabs({
        children: () => (<h1>Child</h1>),
        route,
        router,
    });

    await user.click(screen.getByRole('button', {name: 'tabTitle2'}));

    expect(router.navigate).toBeCalledWith('route2', {id: 1});
});
