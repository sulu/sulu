// @flow
import metadataStore from '../../stores/MetadataStore';
import resourceMetadataStore from '../../../../stores/ResourceMetadataStore';

jest.mock('../../../../stores/ResourceMetadataStore', () => ({
    loadConfiguration: jest.fn(),
}));

test('Return datagrid fields for given resourceKey from ResourceMetadataStore', () => {
    const snippetMetadata = {
        datagrid: {
            id: {},
            title: {
                sortable: true,
            },
        },
    };
    const contactMetadata = {
        datagrid: {
            id: {},
            firstName: {
                sortable: true,
            },
            lastName: {
                sortable: true,
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
        expect(snippetFields.title).toEqual({sortable: true});

        expect(Object.keys(contactFields)).toHaveLength(3);
        expect(contactFields.id).toEqual({});
        expect(contactFields.firstName).toEqual({sortable: true});
        expect(contactFields.lastName).toEqual({sortable: true});
    });
});

test('Throw exception if no datagrid fields for given resourceKey are available', () => {
    const contactMetadata = {};
    const contactPromise = Promise.resolve(contactMetadata);

    resourceMetadataStore.loadConfiguration.mockReturnValue(contactPromise);

    return metadataStore.getSchema('contacts').catch((error) => {
        expect(error.toString()).toEqual(expect.stringContaining('"contacts"'));
    });
});
