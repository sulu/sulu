// @flow
import getViewKeyFromRoute from '../getViewKeyFromRoute';
import Route from '../Route';

test('Return just the route name if no rerender attributes are given', () => {
    const route = new Route({
        name: 'route1',
        path: '/route1',
        type: 'view1',
    });
    expect(getViewKeyFromRoute(route, {})).toEqual('route1');
});

test('Return the route name rerender attributes are not given', () => {
    const route = new Route({
        name: 'route1',
        options: {},
        path: '/route1',
        rerenderAttributes: ['webspace', 'locale'],
        type: 'view1',
    });

    expect(getViewKeyFromRoute(route, {})).toEqual('route1');
});

test('Return the route name with the value of the rerender attributes', () => {
    const route = new Route({
        name: 'route1',
        path: '/route1',
        rerenderAttributes: ['webspace', 'locale'],
        type: 'view1',
    });

    expect(getViewKeyFromRoute(route, {webspace: 'sulu', locale: 'de'})).toEqual('route1-sulu__de');
});
