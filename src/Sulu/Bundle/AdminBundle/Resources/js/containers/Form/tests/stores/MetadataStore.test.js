// @flow
import metadataStore from '../../stores/MetadataStore';
import resourceMetadataStore from '../../../../stores/ResourceMetadataStore';

jest.mock('../../../../stores/ResourceMetadataStore', () => ({
    loadConfiguration: jest.fn(),
}));

test('Return form fields for given resourceKey from ResourceMetadataStore', () => {
    const snippetMetadata = {
        form: {
            id: {},
            title: {
                highlight: true,
            },
        },
    };

    const contactMetadata = {
        form: {
            id: {},
            firstName: {
                highlight: true,
            },
            lastName: {
                highlight: true,
            },
        },
    };
    const snippetPromise = Promise.resolve(snippetMetadata);
    const contactPromise = Promise.resolve(contactMetadata);
    resourceMetadataStore.loadConfiguration.mockImplementation((resourceKey) => {
        switch(resourceKey) {
            case 'snippets':
                return snippetPromise;
            case 'contacts':
                return contactPromise;
        }
    });

    const snippetFieldPromise = metadataStore.getSchema('snippets');
    const contactFieldPromise = metadataStore.getSchema('contacts');

    return Promise.all([snippetFieldPromise, contactFieldPromise]).then(([snippetFields, contactFields]) => {
        expect(Object.keys(snippetFields)).toHaveLength(2);
        expect(snippetFields.id).toEqual({});
        expect(snippetFields.title).toEqual({highlight: true});

        expect(Object.keys(contactFields)).toHaveLength(3);
        expect(contactFields.id).toEqual({});
        expect(contactFields.firstName).toEqual({highlight: true});
        expect(contactFields.lastName).toEqual({highlight: true});
    });
});

test('Throw exception if no form fields for given resourceKey are available', () => {
    const contactMetadata = {};
    const contactPromise = Promise.resolve(contactMetadata);

    resourceMetadataStore.loadConfiguration.mockReturnValue(contactPromise);

    return metadataStore.getSchema('contacts').catch((error) => {
        expect(error.toString()).toEqual(expect.stringContaining('"contacts"'));
    });
});
