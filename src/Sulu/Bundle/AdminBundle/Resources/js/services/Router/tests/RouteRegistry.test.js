/* eslint-disable flowtype/require-valid-file-annotation */
import routeRegistry from '../registries/RouteRegistry';

beforeEach(() => {
    routeRegistry.clear();
});

test('Clear routes from RouteRegistry', () => {
    routeRegistry.add({
        name:'route',
        view: 'view',
        pattern: '/route',
    });

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

    routeRegistry.add(route1);
    routeRegistry.add(route2);

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

    routeRegistry.add(route);

    expect(() => routeRegistry.add(route)).toThrow('test_route');
});
