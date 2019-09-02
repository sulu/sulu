//@flow
import {toJS} from 'mobx';
import routeRegistry from '../../registries/routeRegistry';

beforeEach(() => {
    routeRegistry.clear();
});

test('Clear routes from RouteRegistry', () => {
    routeRegistry.addCollection([
        {
            name: 'route',
            view: 'view',
            path: '/route',
            options: {},
            attributeDefaults: {},
            rerenderAttributes: [],
        },
    ]);

    expect(Object.keys(routeRegistry.routes)).toHaveLength(1);

    routeRegistry.clear();
    expect(Object.keys(routeRegistry.routes)).toHaveLength(0);
});

test('Get routes from RouteRegistry', () => {
    const route1 = {
        name: 'route1',
        view: 'view1',
        path: '/route/1',
        options: {
            test: 'value',
        },
        attributeDefaults: {},
        rerenderAttributes: [],
    };
    const route2 = {
        name: 'route2',
        view: 'view2',
        path: '/route/2',
        options: {
            test2: 'value2',
        },
        attributeDefaults: {},
        rerenderAttributes: [],
    };

    routeRegistry.addCollection([route1, route2]);

    const routes = routeRegistry.getAll();

    expect(Object.keys(routes)).toHaveLength(2);
    expect(toJS(routes.route1)).toEqual({
        name: 'route1',
        view: 'view1',
        path: '/route/1',
        options: {
            test: 'value',
        },
        children: [],
        attributeDefaults: {},
        rerenderAttributes: [],
        parent: undefined,
    });
    expect(toJS(routes.route2)).toEqual({
        name: 'route2',
        view: 'view2',
        path: '/route/2',
        options: {
            test2: 'value2',
        },
        children: [],
        attributeDefaults: {},
        rerenderAttributes: [],
        parent: undefined,
    });
});

test('Add a route collection to the RouteRegistry', () => {
    const route1 = {
        name: 'route1',
        view: 'view1',
        path: '/route/1',
        options: {
            test: 'value',
        },
        attributeDefaults: {},
        rerenderAttributes: [],
    };

    const route2 = {
        name: 'route2',
        view: 'view2',
        path: '/route/2',
        options: {
            test2: 'value2',
        },
        attributeDefaults: {},
        rerenderAttributes: [],
    };

    routeRegistry.addCollection([route1, route2]);

    expect(toJS(routeRegistry.get('route1'))).toEqual({
        name: 'route1',
        view: 'view1',
        path: '/route/1',
        options: {
            test: 'value',
        },
        parent: undefined,
        children: [],
        attributeDefaults: {},
        rerenderAttributes: [],
    });
    expect(toJS(routeRegistry.get('route2'))).toEqual({
        name: 'route2',
        view: 'view2',
        path: '/route/2',
        options: {
            test2: 'value2',
        },
        parent: undefined,
        children: [],
        attributeDefaults: {},
        rerenderAttributes: [],
    });
});

test('Add route with existing key should throw', () => {
    const route = {
        name: 'test_route',
        view: 'view',
        path: '/route',
        options: {},
        attributeDefaults: {},
        rerenderAttributes: [],
    };

    routeRegistry.addCollection([route]);

    expect(() => routeRegistry.addCollection([route])).toThrow('test_route');
});

test('Set parent and children routes based on passed RouteConfig', () => {
    routeRegistry.addCollection([
        {
            name: 'sulu_snippet.form',
            view: 'sulu_admin.tab',
            path: '/snippets/:uuid',
            options: {},
            attributeDefaults: {},
            rerenderAttributes: [],
        },
        {
            name: 'sulu_snippet.form.details',
            parent: 'sulu_snippet.form',
            view: 'sulu_admin.form',
            path: '/details',
            options: {},
            attributeDefaults: {},
            rerenderAttributes: [],
        },
        {
            name: 'sulu_snippet.form.taxonomy',
            parent: 'sulu_snippet.form',
            view: 'sulu_admin.form',
            path: '/taxonomy',
            options: {},
            attributeDefaults: {},
            rerenderAttributes: [],
        },
    ]);

    const formRoute = routeRegistry.get('sulu_snippet.form');
    const detailRoute = routeRegistry.get('sulu_snippet.form.details');
    const taxonomyRoute = routeRegistry.get('sulu_snippet.form.taxonomy');

    expect(formRoute.name).toBe('sulu_snippet.form');
    expect(formRoute.children).toHaveLength(2);
    expect(formRoute.children[0]).toBe(detailRoute);
    expect(formRoute.children[1]).toBe(taxonomyRoute);
    expect(detailRoute.name).toBe('sulu_snippet.form.details');
    expect(detailRoute.parent).toBe(formRoute);
    expect(taxonomyRoute.name).toBe('sulu_snippet.form.taxonomy');
    expect(taxonomyRoute.parent).toBe(formRoute);
});

test('Get a non-existing route should throw an exception', () => {
    expect(() => routeRegistry.get('test')).toThrow(/"test"/);
});
