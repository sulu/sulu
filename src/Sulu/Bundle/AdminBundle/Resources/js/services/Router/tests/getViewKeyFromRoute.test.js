// @flow
import getViewKeyFromRoute from '../getViewKeyFromRoute';
import type {Route} from '../types';

test('Return just the route name if no rerender attributes are given', () => {
    const route: Route = {
        attributeDefaults: {},
        availableAttributes: [],
        children: [],
        name: 'route1',
        options: {},
        parent: undefined,
        path: '/route1',
        regexp: new RegExp('^/route1$'),
        rerenderAttributes: [],
        type: 'view1',
    };
    expect(getViewKeyFromRoute(route, {})).toEqual('route1');
});

test('Return the route name rerender attributes are not given', () => {
    const route: Route = {
        attributeDefaults: {},
        availableAttributes: [],
        children: [],
        name: 'route1',
        options: {},
        parent: undefined,
        path: '/route1',
        regexp: new RegExp('^/route1$'),
        rerenderAttributes: ['webspace', 'locale'],
        type: 'view1',
    };

    expect(getViewKeyFromRoute(route, {})).toEqual('route1');
});

test('Return the route name with the value of the rerender attributes', () => {
    const route: Route = {
        attributeDefaults: {},
        availableAttributes: [],
        children: [],
        name: 'route1',
        options: {},
        parent: undefined,
        path: '/route1',
        regexp: new RegExp('^/route1$'),
        rerenderAttributes: ['webspace', 'locale'],
        type: 'view1',
    };

    expect(getViewKeyFromRoute(route, {webspace: 'sulu', locale: 'de'})).toEqual('route1-sulu__de');
});
