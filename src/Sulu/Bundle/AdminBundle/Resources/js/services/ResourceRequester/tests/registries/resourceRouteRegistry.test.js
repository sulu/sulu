// @flow
import SymfonyRouting from 'fos-jsrouting/router';
import resourceRouteRegistry from '../../registries/resourceRouteRegistry';

test('Set and get endpoints for given key', () => {
    SymfonyRouting.generate.mockImplementation((routeName, {value}) => {
        return routeName + '?value=' + value;
    });

    resourceRouteRegistry.setEndpoints({
        snippets: {
            routes: {
                detail: 'get_snippet',
                list: 'get_snippets',
            },
        },
    });

    expect(resourceRouteRegistry.getDetailUrl('snippets', {value: 1})).toEqual('get_snippet?value=1');
    expect(resourceRouteRegistry.getListUrl('snippets', {value: 2})).toEqual('get_snippets?value=2');
});

test('Set and get endpoints for given key with date parameter', () => {
    SymfonyRouting.generate.mockImplementation((routeName, {value}) => {
        return routeName + '?value=' + value;
    });

    resourceRouteRegistry.setEndpoints({
        snippets: {
            routes: {
                detail: 'get_snippet',
                list: 'get_snippets',
            },
        },
    });

    expect(resourceRouteRegistry.getDetailUrl('snippets', {value: new Date('2013-12-24')}))
        .toEqual('get_snippet?value=2013-12-24');
    expect(resourceRouteRegistry.getListUrl('snippets', {value: new Date('2020-09-07')}))
        .toEqual('get_snippets?value=2020-09-07');
});

test('Throw exception when getting detail url for not existing key', () => {
    expect(() => resourceRouteRegistry.getDetailUrl('not-existing')).toThrow(/"not-existing"/);
});

test('Throw exception when getting list url for not existing key', () => {
    expect(() => resourceRouteRegistry.getListUrl('not-existing')).toThrow(/"not-existing"/);
});

test('Throw exception when getting detail url for not existing detail url', () => {
    resourceRouteRegistry.setEndpoints({
        existing: {
            routes: {},
        },
    });

    expect(() => resourceRouteRegistry.getDetailUrl('existing'))
        .toThrow(/no detail route for the resourceKey "existing"/);
});

test('Throw exception when getting detail url for not existing list url', () => {
    resourceRouteRegistry.setEndpoints({
        existing: {
            routes: {},
        },
    });

    expect(() => resourceRouteRegistry.getListUrl('existing'))
        .toThrow(/no list route for the resourceKey "existing"/);
});
