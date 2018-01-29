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

test('Return form fields for given resourceKey and type', () => {
    const snippetSidebarFormMetadata = {
        title: {},
    };
    const snippetFooterFormMetadata = {
        title: {},
        description: {},
    };

    const snippetMetadata = {
        types: {
            sidebar: {
                title: 'Sidebar',
                form: snippetSidebarFormMetadata,
            },
            footer: {
                title: 'Footer',
                form: snippetFooterFormMetadata,
            },
        },
    };

    const snippetPromise = Promise.resolve(snippetMetadata);
    resourceMetadataStore.loadConfiguration.mockReturnValue(snippetPromise);

    const snippetSidebarFieldPromise = metadataStore.getSchema('snippets', 'sidebar');
    const snippetFooterFieldPromise = metadataStore.getSchema('snippets', 'footer');

    return Promise.all([
        snippetSidebarFieldPromise,
        snippetFooterFieldPromise,
    ]).then(([snippetSidebarFields, snippetFooterFields]) => {
        expect(snippetSidebarFields).toBe(snippetSidebarFormMetadata);
        expect(snippetFooterFields).toBe(snippetFooterFormMetadata);
    });
});

test('Throw if a type is requested, but the given resourceKey does not have type support', (done) => {
    const snippetMetadata = {
        form: {
            title: {},
            description: {},
        },
    };

    const snippetPromise = Promise.resolve(snippetMetadata);
    resourceMetadataStore.loadConfiguration.mockReturnValue(snippetPromise);

    const snippetFieldsPromise = metadataStore.getSchema('snippets', 'sidebar');

    return snippetFieldsPromise.catch((error) => {
        expect(error.toString()).toEqual(expect.stringContaining('does not support types'));
        expect(error.toString()).toEqual(expect.stringContaining('"snippets"'));
        done();
    });
});

test('Throw if a type is omitted, but the given reosurceKey has type support', (done) => {
    const snippetMetadata = {
        types: {
            sidebar: {
                form: {
                    title: {},
                    description: {},
                },
            },
        },
    };

    const snippetPromise = Promise.resolve(snippetMetadata);
    resourceMetadataStore.loadConfiguration.mockReturnValue(snippetPromise);

    const snippetFieldsPromise = metadataStore.getSchema('snippets');

    return snippetFieldsPromise.catch((error) => {
        expect(error.toString()).toEqual(expect.stringContaining('requires a type'));
        expect(error.toString()).toEqual(expect.stringContaining('"snippets"'));
        done();
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

test('Throw exception if no form fields for given resourceKey and type are available', () => {
    const snippetMetadata = {
        types: {
            default: {},
        },
    };
    const snippetPromise = Promise.resolve(snippetMetadata);

    resourceMetadataStore.loadConfiguration.mockReturnValue(snippetPromise);

    return metadataStore.getSchema('snippets', 'default').catch((error) => {
        expect(error.toString()).toEqual(expect.stringContaining('no form schema'));
        expect(error.toString()).toEqual(expect.stringContaining('"snippets"'));
        expect(error.toString()).toEqual(expect.stringContaining('"default"'));
    });
});

test('Return available types for given resourceKey', () => {
    const snippetMetadata = {
        types: {
            sidebar: {
                title: 'Sidebar Snippet',
            },
            footer: {},
        },
    };
    const snippetPromise = Promise.resolve(snippetMetadata);
    resourceMetadataStore.loadConfiguration.mockReturnValue(snippetPromise);

    const snippetTypesPromise = metadataStore.getSchemaTypes('snippets');

    return snippetTypesPromise.then((snippetTypes) => {
        expect(snippetTypes).toMatchSnapshot();
    });
});

test('Return empty object as available types for given resourceKey if types are not supported', () => {
    const snippetMetadata = {
        form: {},
    };
    const snippetPromise = Promise.resolve(snippetMetadata);
    resourceMetadataStore.loadConfiguration.mockReturnValue(snippetPromise);

    const snippetTypesPromise = metadataStore.getSchemaTypes('snippets');

    return snippetTypesPromise.then((snippetTypes) => {
        expect(snippetTypes).toEqual({});
    });
});
