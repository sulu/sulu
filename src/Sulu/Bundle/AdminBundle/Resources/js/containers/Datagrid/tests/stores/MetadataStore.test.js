// @flow
import metadataStore from '../../stores/MetadataStore';
import resourceMetadataStore from '../../../../stores/ResourceMetadataStore';

jest.mock('../../../../stores/ResourceMetadataStore', () => ({
    loadConfiguration: jest.fn(),
}));

test('Return list fields for given resourceKey from ResourceMetadataStore', () => {
    const resourceMetadata = {
        snippets: {
            list: {
                id: {},
                title: {
                    sortable: true,
                },
            },
        },
        contacts: {
            list: {
                id: {},
                firstName: {
                    sortable: true,
                },
                lastName: {
                    sortable: true,
                },
            },
        },
    };
    resourceMetadataStore.loadConfiguration.mockImplementation((resourceKey) => resourceMetadata[resourceKey]);

    const snippetFields = metadataStore.getSchema('snippets');
    expect(Object.keys(snippetFields)).toHaveLength(2);
    expect(snippetFields.id).toEqual({});
    expect(snippetFields.title).toEqual({sortable: true});

    const contactFields = metadataStore.getSchema('contacts');
    expect(Object.keys(contactFields)).toHaveLength(3);
    expect(contactFields.id).toEqual({});
    expect(contactFields.firstName).toEqual({sortable: true});
    expect(contactFields.lastName).toEqual({sortable: true});
});

test('Throw exception if no list fields for given resourceKey are available', () => {
    const resourceMetadata = {
        snippets: {},
        contacts: {},
    };
    resourceMetadataStore.loadConfiguration.mockImplementation((resourceKey) => resourceMetadata[resourceKey]);

    expect(() => metadataStore.getSchema('snippets')).toThrow(/"snippets"/);
    expect(() => metadataStore.getSchema('contacts')).toThrow(/"contacts"/);
});
