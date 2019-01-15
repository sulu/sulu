// @flow
import resourceMetadataStore from '../ResourceMetadataStore';

jest.mock('../../../services/Requester', () => ({
    get: jest.fn(),
}));

test('Set and get endpoint for given key', () => {
    resourceMetadataStore.setEndpoints({
        snippets: '/admin/api/snippets',
    });
    expect(resourceMetadataStore.getEndpoint('snippets')).toEqual('/admin/api/snippets');
});

test('Throw exception when getting endpoint for not existing key', () => {
    expect(() => resourceMetadataStore.getEndpoint('not-existing')).toThrow(/"not-existing"/);
});
