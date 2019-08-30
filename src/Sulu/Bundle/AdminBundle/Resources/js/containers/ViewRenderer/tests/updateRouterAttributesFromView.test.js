// @flow
import updateRouterAttributesFromView from '../updateRouterAttributesFromView';
import viewRegistry from '../registries/viewRegistry';

jest.mock('../registries/viewRegistry', () => ({
    get: jest.fn(),
}));

test('Return an empty object if the corresponding View has no getDerivedRouterAttributes function', () => {
    viewRegistry.get.mockImplementation((key) => {
        if (key === 'test') {
            return {};
        }
    });

    expect(updateRouterAttributesFromView({
        attributeDefaults: {},
        children: [],
        name: 'test',
        options: {},
        path: '/test',
        parent: undefined,
        rerenderAttributes: [],
        view: 'test',
    }, {})).toEqual({});
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

    expect(updateRouterAttributesFromView({
        attributeDefaults: {},
        children: [],
        name: 'test',
        options: {},
        path: '/test',
        parent: undefined,
        rerenderAttributes: [],
        view: 'test',
    }, {value3: 'test3'})).toEqual({value1: 'test1', value2: 'test2', value3: 'test3', routeName: 'test'});
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

    const route1 = {
        attributeDefaults: {},
        children: [],
        name: 'test1',
        options: {},
        path: '/test',
        parent: undefined,
        rerenderAttributes: [],
        view: 'test1',
    };

    const route2 = {
        attributeDefaults: {},
        children: [],
        name: 'test2',
        options: {},
        path: '/test',
        parent: route1,
        rerenderAttributes: [],
        view: 'test2',
    };

    const route3 = {
        attributeDefaults: {},
        children: [],
        name: 'test3',
        options: {},
        path: '/test',
        parent: route2,
        rerenderAttributes: [],
        view: 'test3',
    };

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
