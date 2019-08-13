// @flow
import metadataStore from '../../stores/MetadataStore';
import generalMetadataStore from '../../../../stores/MetadataStore';

jest.mock('../../../../stores/MetadataStore', () => ({
    loadMetadata: jest.fn(),
}));

test('Return list fields for given resourceKey from MetadataStore', () => {
    const promise = Promise.resolve();
    generalMetadataStore.loadMetadata.mockReturnValue(promise);

    const snippetPromise = metadataStore.getSchema('snippets', {id: 10});
    expect(generalMetadataStore.loadMetadata).toHaveBeenLastCalledWith('list', 'snippets', {id: 10});

    const contactPromise = metadataStore.getSchema('contacts');
    expect(generalMetadataStore.loadMetadata).toHaveBeenLastCalledWith('list', 'contacts', undefined);

    const contactPromise2 = metadataStore.getSchema('contacts', undefined);
    expect(generalMetadataStore.loadMetadata).toHaveBeenLastCalledWith('list', 'contacts', undefined);

    expect(snippetPromise).toEqual(promise);
    expect(contactPromise).toEqual(promise);
    expect(contactPromise2).toEqual(promise);
});
