// @flow
import updateRouterAttributesFromView from '../updateRouterAttributesFromView';
import viewRegistry from '../registries/ViewRegistry';

jest.mock('../registries/ViewRegistry', () => ({
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
                getDerivedRouteAttributes: jest.fn().mockReturnValue({
                    value1: 'test1',
                    value2: 'test2',
                }),
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
    }, {})).toEqual({value1: 'test1', value2: 'test2'});
});

test('Throw an error if attributes returned from the getDerivedRouterAttributes function are not an object', () => {
    viewRegistry.get.mockImplementation((key) => {
        if (key === 'test') {
            return {
                getDerivedRouteAttributes: jest.fn().mockReturnValue('test'),
            };
        }
    });

    expect(() => updateRouterAttributesFromView({
        attributeDefaults: {},
        children: [],
        name: 'test',
        options: {},
        path: '/test',
        parent: undefined,
        rerenderAttributes: [],
        view: 'test',
    }, {})).toThrow(/"getDerivedRouteAttributes".*"test" view/);
});
