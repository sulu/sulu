/* eslint-disable flowtype/require-valid-file-annotation */
import routeStore from '../../stores/RouteStore';

test('get routes from RouteStore', () => {
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

    routeStore.add(route1);
    routeStore.add(route2);

    expect(routeStore.get('route1')).toBe(route1);
    expect(routeStore.get('route2')).toBe(route2);

    expect(routeStore.getAll()).toEqual({
        route1: route1,
        route2: route2,
    });
});
