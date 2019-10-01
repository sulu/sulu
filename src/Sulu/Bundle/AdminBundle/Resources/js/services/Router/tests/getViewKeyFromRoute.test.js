// @flow
import getViewKeyFromRoute from '../getViewKeyFromRoute';

test('Return just the route name if no rerender attributes are given', () => {
    const route = {
        attributeDefaults: {},
        children: [],
        name: 'route1',
        options: {},
        parent: undefined,
        path: '/route1',
        rerenderAttributes: [],
        type: 'view1',
    };
    expect(getViewKeyFromRoute(route, {})).toEqual('route1');
});

test('Return the route name rerender attributes are not given', () => {
    const route = {
        attributeDefaults: {},
        children: [],
        name: 'route1',
        options: {},
        parent: undefined,
        path: '/route1',
        rerenderAttributes: ['webspace', 'locale'],
        type: 'view1',
    };

    expect(getViewKeyFromRoute(route, {})).toEqual('route1');
});

test('Return the route name with the value of the rerender attributes', () => {
    const route = {
        attributeDefaults: {},
        children: [],
        name: 'route1',
        options: {},
        parent: undefined,
        path: '/route1',
        rerenderAttributes: ['webspace', 'locale'],
        type: 'view1',
    };

    expect(getViewKeyFromRoute(route, {webspace: 'sulu', locale: 'de'})).toEqual('route1-sulu__de');
});
