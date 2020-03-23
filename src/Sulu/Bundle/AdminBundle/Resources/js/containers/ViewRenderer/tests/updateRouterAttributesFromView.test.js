// @flow
import updateRouterAttributesFromView from '../updateRouterAttributesFromView';
import viewRegistry from '../registries/viewRegistry';
import Route from '../../../services/Router/Route';

jest.mock('../registries/viewRegistry', () => ({
    get: jest.fn(),
}));

test('Return an empty object if the corresponding View has no getDerivedRouterAttributes function', () => {
    viewRegistry.get.mockImplementation((key) => {
        if (key === 'test') {
            return {};
        }
    });

    expect(updateRouterAttributesFromView(new Route({
        name: 'test',
        path: '/test',
        type: 'test',
    }), {})).toEqual({});
});

test('Return the attributes returned from the getDerivedRouterAttributes function', () => {
    viewRegistry.get.mockImplementation((key) => {
        if (key === 'test') {
            return {
                getDerivedRouteAttributes: jest.fn().mockImplementation((route, attributes) => ({
                    value1: 'test1',
                    value2: 'test2',
                    value3: attributes.value3,
                    routeName: route.name,
                })),
            };
        }
    });

    expect(updateRouterAttributesFromView(new Route({
        name: 'test',
        path: '/test',
        type: 'test',
    }), {value3: 'test3'})).toEqual({value1: 'test1', value2: 'test2', value3: 'test3', routeName: 'test'});
});

test('Return the combined attributes from the current and parent getDerivedrouterAttributes function', () => {
    viewRegistry.get.mockImplementation((key) => {
        if (key === 'test1') {
            return {
                getDerivedRouteAttributes: jest.fn((route, attributes) => ({
                    routeName1: route.name,
                    value1: attributes.value1,
                })),
            };
        }
        if (key === 'test2') {
            return {
                getDerivedRouteAttributes: jest.fn((route, attributes) => ({
                    routeName2: route.name,
                    value2: attributes.value2,
                })),
            };
        }
        if (key === 'test3') {
            return {
                getDerivedRouteAttributes: jest.fn((route, attributes) => ({
                    routeName3: route.name,
                    value3: attributes.value3,
                })),
            };
        }
    });

    const route1 = new Route({
        name: 'test1',
        path: '/test',
        type: 'test1',
    });

    const route2 = new Route({
        name: 'test2',
        path: '/test',
        type: 'test2',
    });

    route2.parent = route1;

    const route3 = new Route({
        name: 'test3',
        path: '/test',
        type: 'test3',
    });

    route3.parent = route2;

    expect(updateRouterAttributesFromView(route3, {value1: 'test1', value2: 'test2', value3: 'test3'}))
        .toEqual({
            value1: 'test1',
            value2: 'test2',
            value3: 'test3',
            routeName1: 'test1',
            routeName2: 'test2',
            routeName3: 'test3',
        });
});
