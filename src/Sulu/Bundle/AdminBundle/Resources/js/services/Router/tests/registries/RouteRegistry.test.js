/* eslint-disable flowtype/require-valid-file-annotation */
import routeRegistry from '../../registries/RouteRegistry';

beforeEach(() => {
    routeRegistry.clear();
});

test('Clear routes from RouteRegistry', () => {
    routeRegistry.addCollection([
        {
            name:'route',
            view: 'view',
            pattern: '/route',
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
        pattern: '/route/1',
        parameters: {
            test: 'value',
        },
    };
    const route2 = {
        name: 'route2',
        view: 'view2',
        pattern: '/route/2',
        parameters: {
            test2: 'value2',
        },
    };

    routeRegistry.addCollection([route1, route2]);

    expect(routeRegistry.get('route1')).toBe(route1);
    expect(routeRegistry.get('route2')).toBe(route2);

    expect(routeRegistry.getAll()).toEqual({
        route1: route1,
        route2: route2,
    });
});

test('Add a route collection to the RouteRegistry', () => {
    const route1 = {
        name: 'route1',
        view: 'view1',
        pattern: '/route/1',
        parameters: {
            test: 'value',
        },
    };

    const route2 = {
        name: 'route2',
        view: 'view2',
        pattern: '/route/2',
        parameters: {
            test2: 'value2',
        },
    };

    routeRegistry.addCollection([route1, route2]);

    expect(routeRegistry.get('route1')).toBe(route1);
    expect(routeRegistry.get('route2')).toBe(route2);
});

test('Add route with existing key should throw', () => {
    const route = {
        name: 'test_route',
        view: 'view',
        pattern: '/route',
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
        },
        {
            name: 'sulu_snippet.form.detail',
            parent: 'sulu_snippet.form',
            view: 'sulu_admin.form',
            path: '/detail',
        },
        {
            name: 'sulu_snippet.form.taxonomy',
            parent: 'sulu_snippet.form',
            view: 'sulu_admin.form',
            path: '/taxonomy',
        },
    ]);

    const formRoute = routeRegistry.get('sulu_snippet.form');
    const detailRoute = routeRegistry.get('sulu_snippet.form.detail');
    const taxonomyRoute = routeRegistry.get('sulu_snippet.form.taxonomy');

    expect(formRoute.name).toBe('sulu_snippet.form');
    expect(formRoute.children).toEqual([detailRoute, taxonomyRoute]);
    expect(detailRoute.name).toBe('sulu_snippet.form.detail');
    expect(detailRoute.parent).toBe(formRoute);
    expect(taxonomyRoute.name).toBe('sulu_snippet.form.taxonomy');
    expect(taxonomyRoute.parent).toBe(formRoute);
});
