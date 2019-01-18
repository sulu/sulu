// @flow
import resourceEndpointRegistry from '../../registries/ResourceEndpointRegistry';

test('Set and get endpoint for given key', () => {
    resourceEndpointRegistry.setEndpoints({
        snippets: '/admin/api/snippets',
    });
    expect(resourceEndpointRegistry.getEndpoint('snippets')).toEqual('/admin/api/snippets');
});

test('Throw exception when getting endpoint for not existing key', () => {
    expect(() => resourceEndpointRegistry.getEndpoint('not-existing')).toThrow(/"not-existing"/);
});
