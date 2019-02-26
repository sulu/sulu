// @flow
import SymfonyRouting from 'fos-jsrouting/router';
import resourceEndpointRegistry from '../../registries/ResourceEndpointRegistry';

test('Set and get endpoints for given key', () => {
    SymfonyRouting.generate.mockImplementation((routeName, {value}) => {
        return routeName + '?value=' + value;
    });

    resourceEndpointRegistry.setEndpoints({
        snippets: {
            endpoint: {
                detail: 'get_snippet',
                list: 'get_snippets',
            },
        },
    });

    expect(resourceEndpointRegistry.getDetailUrl('snippets', {value: 1})).toEqual('get_snippet?value=1');
    expect(resourceEndpointRegistry.getListUrl('snippets', {value: 2})).toEqual('get_snippets?value=2');
});

test('Throw exception when getting detail url for not existing key', () => {
    expect(() => resourceEndpointRegistry.getDetailUrl('not-existing')).toThrow(/"not-existing"/);
});

test('Throw exception when getting detail url for not existing key', () => {
    expect(() => resourceEndpointRegistry.getListUrl('not-existing')).toThrow(/"not-existing"/);
});

test('Throw exception when getting detail url for not existing detail url', () => {
    resourceEndpointRegistry.setEndpoints({
        existing: {
            endpoint: {},
        },
    });

    expect(() => resourceEndpointRegistry.getDetailUrl('existing'))
        .toThrow(/no detail endpoint for the resourceKey "existing"/);
});

test('Throw exception when getting detail url for not existing list url', () => {
    resourceEndpointRegistry.setEndpoints({
        existing: {
            endpoint: {},
        },
    });

    expect(() => resourceEndpointRegistry.getListUrl('existing'))
        .toThrow(/no list endpoint for the resourceKey "existing"/);
});
