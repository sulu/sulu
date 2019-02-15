// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import Router from '../../../services/Router';
import Tabs from '../Tabs';

jest.mock('../../../services/Router', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Should render the children after the tabs', () => {
    const childRoute1 = {
        attributeDefaults: {},
        children: [],
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        parent: null,
        path: '/route1',
        rerenderAttributes: [],
        view: 'route1',
    };
    const childRoute2 = {
        attributeDefaults: {},
        children: [],
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
        parent: null,
        path: '/route2',
        rerenderAttributes: [],
        view: 'route1',
    };

    const route = {
        attributeDefaults: {},
        children: [
            childRoute1,
            childRoute2,
        ],
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        parent: null,
        path: '/parent',
        rerenderAttributes: [],
        view: 'route1',
    };

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
    expect(render(<Tabs route={route} router={router}>{() => (<Child />)}</Tabs>)).toMatchSnapshot();
});

test('Should consider the tabOrder when rendering the tabs', () => {
    const childRoute1 = {
        attributeDefaults: {},
        children: [],
        name: 'route1',
        options: {
            tabOrder: 40,
            tabTitle: 'tabTitle1',
        },
        parent: null,
        path: '/route1',
        rerenderAttributes: [],
        view: 'route1',
    };
    const childRoute2 = {
        attributeDefaults: {},
        children: [],
        name: 'route2',
        options: {
            tabOrder: 30,
            tabTitle: 'tabTitle2',
        },
        parent: null,
        path: '/route2',
        rerenderAttributes: [],
        view: 'route2',
    };
    const childRoute3 = {
        attributeDefaults: {},
        children: [],
        name: 'route3',
        options: {
            tabOrder: 50,
            tabTitle: 'tabTitle3',
        },
        parent: null,
        path: '/route3',
        rerenderAttributes: [],
        view: 'route3',
    };

    const route = {
        attributeDefaults: {},
        children: [
            childRoute1,
            childRoute2,
            childRoute3,
        ],
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        parent: null,
        path: '/parent',
        rerenderAttributes: [],
        view: 'route1',
    };

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
    const tabs = mount(<Tabs route={route} router={router}>{() => (<Child />)}</Tabs>);

    expect(tabs.find('Tab').at(0).text()).toEqual('tabTitle2');
    expect(tabs.find('Tab').at(1).text()).toEqual('tabTitle1');
    expect(tabs.find('Tab').at(2).text()).toEqual('tabTitle3');
});

test('Should mark currently active tab as selected according to prop', (done) => {
    const childRoute1 = {
        attributeDefaults: {},
        children: [],
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        parent: null,
        path: '/route1',
        rerenderAttributes: [],
        view: 'route1',
    };
    const childRoute2 = {
        attributeDefaults: {},
        children: [],
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
        parent: null,
        path: '/route2',
        rerenderAttributes: [],
        view: 'route1',
    };

    const route = {
        attributeDefaults: {},
        children: [
            childRoute1,
            childRoute2,
        ],
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        parent: null,
        path: '/parent',
        rerenderAttributes: [],
        view: 'route1',
    };

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
    const tabs = mount(
        <Tabs route={route} router={router} selectedIndex={0}>{() => (<Child route={activeRoute} />)}</Tabs>
    );

    setTimeout(() => {
        expect(router.redirect).not.toBeCalled();
        expect(tabs.find('Tab').at(0).prop('selected')).toEqual(true);
        expect(tabs.find('Tab').at(1).prop('selected')).toEqual(false);
        done();
    });
});

test('Should mark currently active tab as selected', (done) => {
    const childRoute1 = {
        attributeDefaults: {},
        children: [],
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        parent: null,
        path: '/route1',
        rerenderAttributes: [],
        view: 'route1',
    };
    const childRoute2 = {
        attributeDefaults: {},
        children: [],
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
        parent: null,
        path: '/route2',
        rerenderAttributes: [],
        view: 'route1',
    };

    const route = {
        attributeDefaults: {},
        children: [
            childRoute1,
            childRoute2,
        ],
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        parent: null,
        path: '/parent',
        rerenderAttributes: [],
        view: 'route1',
    };

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
    const tabs = mount(
        <Tabs route={route} router={router}>{() => (<Child route={activeRoute} />)}</Tabs>
    );

    setTimeout(() => {
        expect(router.redirect).not.toBeCalled();
        expect(tabs.find('Tab').at(0).prop('selected')).toEqual(false);
        expect(tabs.find('Tab').at(1).prop('selected')).toEqual(true);
        done();
    });
});

test('Should redirect to child route with highest priority if no tab is active by default', (done) => {
    const childRoute1 = {
        attributeDefaults: {},
        children: [],
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        parent: null,
        path: '/route1',
        rerenderAttributes: [],
        view: 'route1',
    };
    const childRoute2 = {
        attributeDefaults: {},
        children: [],
        name: 'route2',
        options: {
            tabPriority: 100,
            tabTitle: 'tabTitle2',
        },
        parent: null,
        path: '/route2',
        rerenderAttributes: [],
        view: 'route1',
    };

    const route = {
        attributeDefaults: {},
        children: [
            childRoute1,
            childRoute2,
        ],
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        parent: null,
        path: '/parent',
        rerenderAttributes: [],
        view: 'route1',
    };

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
    mount(<Tabs route={route} router={router}>{() => (<Child />)}</Tabs>);

    setTimeout(() => {
        expect(router.redirect).toBeCalledWith('route2', attributes);
        done();
    });
});

test('Should redirect to child route from props with highest priority if no tab is active by default', (done) => {
    const childRoute1 = {
        attributeDefaults: {},
        children: [],
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        parent: null,
        path: '/route1',
        rerenderAttributes: [],
        view: 'route1',
    };
    const childRoute2 = {
        attributeDefaults: {},
        children: [],
        name: 'route2',
        options: {
            tabPriority: 100,
            tabTitle: 'tabTitle2',
        },
        parent: null,
        path: '/route2',
        rerenderAttributes: [],
        view: 'route1',
    };

    const childRoutes = [childRoute1, childRoute2];

    const route = {
        attributeDefaults: {},
        children: [],
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        parent: null,
        path: '/parent',
        rerenderAttributes: [],
        view: 'route1',
    };

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
    mount(<Tabs route={route} routeChildren={childRoutes} router={router}>{() => (<Child />)}</Tabs>);

    setTimeout(() => {
        expect(router.redirect).toBeCalledWith('route2', attributes);
        done();
    });
});

test('Navigate to tab if it was clicked', () => {
    const childRoute1 = {
        attributeDefaults: {},
        children: [],
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
        parent: null,
        path: '/route1',
        rerenderAttributes: [],
        view: 'route1',
    };
    const childRoute2 = {
        attributeDefaults: {},
        children: [],
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
        parent: null,
        path: '/route2',
        rerenderAttributes: [],
        view: 'route1',
    };

    const route = {
        attributeDefaults: {},
        children: [
            childRoute1,
            childRoute2,
        ],
        name: 'parent',
        options: {
            resourceKey: 'test',
        },
        parent: null,
        path: '/parent',
        rerenderAttributes: [],
        view: 'route1',
    };

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
    const tabs = mount(<Tabs route={route} router={router}>{() => (<Child />)}</Tabs>);

    tabs.find('Tab button').at(1).simulate('click');
    expect(router.navigate).toBeCalledWith('route2', attributes);
});
