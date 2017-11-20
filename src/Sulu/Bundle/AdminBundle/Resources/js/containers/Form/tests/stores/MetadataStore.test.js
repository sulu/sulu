// @flow
import metadataStore from '../../stores/MetadataStore';
import resourceMetadataStore from '../../../../stores/ResourceMetadataStore';

jest.mock('../../../../stores/ResourceMetadataStore', () => ({
    loadConfiguration: jest.fn(),
}));

test('Return form fields for given resourceKey from ResourceMetadataStore', () => {
    const resourceMetadata = {
        snippets: {
            form: {
                id: {},
                title: {
                    highlight: true,
                },
            },
        },
        contacts: {
            form: {
                id: {},
                firstName: {
                    highlight: true,
                },
                lastName: {
                    highlight: true,
                },
            },
        },
    };
    resourceMetadataStore.loadConfiguration.mockImplementation((resourceKey) => resourceMetadata[resourceKey]);

    const snippetFields = metadataStore.getFields('snippets');
    expect(Object.keys(snippetFields)).toHaveLength(2);
    expect(snippetFields.id).toEqual({});
    expect(snippetFields.title).toEqual({highlight: true});

    const contactFields = metadataStore.getFields('contacts');
    expect(Object.keys(contactFields)).toHaveLength(3);
    expect(contactFields.id).toEqual({});
    expect(contactFields.firstName).toEqual({highlight: true});
    expect(contactFields.lastName).toEqual({highlight: true});
});

test('Throw exception if no form fields for given resourceKey are available', () => {
    const resourceMetadata = {
        snippets: {},
        contacts: {},
    };
    resourceMetadataStore.loadConfiguration.mockImplementation((resourceKey) => resourceMetadata[resourceKey]);

    expect(() => metadataStore.getFields('snippets')).toThrow(/"snippets"/);
    expect(() => metadataStore.getFields('contacts')).toThrow(/"contacts"/);
});
