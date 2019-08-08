// @flow
import metadataStore from '../../stores/MetadataStore';
import generalMetadataStore from '../../../../stores/MetadataStore';

jest.mock('../../../../stores/MetadataStore', () => ({
    loadMetadata: jest.fn(),
}));

test('Return list fields for given resourceKey from MetadataStore', () => {
    const promise = Promise.resolve();
    generalMetadataStore.loadMetadata.mockReturnValue(promise);

    const snippetPromise = metadataStore.getSchema('snippets');
    expect(generalMetadataStore.loadMetadata).toHaveBeenLastCalledWith('list', 'snippets', undefined);

    const contactPromise = metadataStore.getSchema('contacts');
    expect(generalMetadataStore.loadMetadata).toHaveBeenLastCalledWith('list', 'contacts', undefined);

    expect(snippetPromise).toEqual(promise);
    expect(contactPromise).toEqual(promise);
});
