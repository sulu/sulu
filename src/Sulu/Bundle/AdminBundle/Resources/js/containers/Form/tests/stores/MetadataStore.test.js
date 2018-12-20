// @flow
import metadataStore from '../../stores/MetadataStore';
import generalMetadataStore from '../../../../stores/MetadataStore';

jest.mock('../../../../stores/MetadataStore', () => ({
    loadMetadata: jest.fn(),
}));

test('Return form and schema fields for given resourceKey from ResourceMetadataStore', () => {
    const snippetMetadata = {
        form: {
            id: {},
            title: {
                highlight: true,
            },
        },
        schema: {
            required: ['title'],
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
        schema: {
            required: ['firstName', 'lastName'],
        },
    };

    const snippetPromise = Promise.resolve(snippetMetadata);
    const contactPromise = Promise.resolve(contactMetadata);
    generalMetadataStore.loadMetadata.mockImplementation((type, resourceKey) => {
        switch (resourceKey) {
            case 'snippets':
                return snippetPromise;
            case 'contacts':
                return contactPromise;
        }
    });

    const snippetFieldPromise = metadataStore.getSchema('snippets');
    const contactFieldPromise = metadataStore.getSchema('contacts');

    const snippetSchemaPromise = metadataStore.getJsonSchema('snippets');
    const contactSchemaPromise = metadataStore.getJsonSchema('contacts');

    return Promise.all([
        snippetFieldPromise,
        contactFieldPromise,
        snippetSchemaPromise,
        contactSchemaPromise]
    ).then(([snippetFields, contactFields, snippetSchema, contactSchema]) => {
        expect(Object.keys(snippetFields)).toHaveLength(2);
        expect(snippetFields.id).toEqual({});
        expect(snippetFields.title).toEqual({highlight: true});

        expect(snippetSchema.required).toEqual(['title']);

        expect(Object.keys(contactFields)).toHaveLength(3);
        expect(contactFields.id).toEqual({});
        expect(contactFields.firstName).toEqual({highlight: true});
        expect(contactFields.lastName).toEqual({highlight: true});

        expect(contactSchema.required).toEqual(['firstName', 'lastName']);
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
    generalMetadataStore.loadMetadata.mockReturnValue(snippetPromise);

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

test('Return schema for given resourceKey and type', () => {
    const snippetSidebarSchema = {
        required: ['title'],
    };
    const snippetFooterSchema = {
        required: ['title', 'description'],
    };

    const snippetMetadata = {
        types: {
            sidebar: {
                title: 'Sidebar',
                schema: snippetSidebarSchema,
            },
            footer: {
                title: 'Footer',
                schema: snippetFooterSchema,
            },
        },
    };

    const snippetPromise = Promise.resolve(snippetMetadata);
    generalMetadataStore.loadMetadata.mockReturnValue(snippetPromise);

    const snippetSidebarSchemaPromise = metadataStore.getJsonSchema('snippets', 'sidebar');
    const snippetFooterSchemaPromise = metadataStore.getJsonSchema('snippets', 'footer');

    return Promise.all([
        snippetSidebarSchemaPromise,
        snippetFooterSchemaPromise,
    ]).then(([snippetSidebar, snippetFooter]) => {
        expect(snippetSidebar).toBe(snippetSidebarSchema);
        expect(snippetFooter).toBe(snippetFooterSchema);
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
    generalMetadataStore.loadMetadata.mockReturnValue(snippetPromise);

    const snippetFieldsPromise = metadataStore.getSchema('snippets', 'sidebar');

    return snippetFieldsPromise.catch((error) => {
        expect(generalMetadataStore.loadMetadata).toBeCalledWith('form', 'snippets');
        expect(error.toString()).toEqual(expect.stringContaining('does not support types'));
        expect(error.toString()).toEqual(expect.stringContaining('"snippets"'));
        done();
    });
});

test('Throw if a schema for a type is requested, but the given resourceKey does not have type support', (done) => {
    const snippetMetadata = {
        form: {
            title: {},
            description: {},
        },
    };

    const snippetPromise = Promise.resolve(snippetMetadata);
    generalMetadataStore.loadMetadata.mockReturnValue(snippetPromise);

    const snippetFieldsPromise = metadataStore.getJsonSchema('snippets', 'sidebar');

    return snippetFieldsPromise.catch((error) => {
        expect(generalMetadataStore.loadMetadata).toBeCalledWith('form', 'snippets');
        expect(error.toString()).toEqual(expect.stringContaining('does not support types'));
        expect(error.toString()).toEqual(expect.stringContaining('"snippets"'));
        done();
    });
});

test('Throw if a type is omitted, but the given reosurceKey has type support', (done) => {
    const snippetMetadata = {
        types: {
            sidebar: {
                schema: {
                    title: {},
                    description: {},
                },
            },
        },
    };

    const snippetPromise = Promise.resolve(snippetMetadata);
    generalMetadataStore.loadMetadata.mockReturnValue(snippetPromise);

    const snippetSchemaPromise = metadataStore.getSchema('snippets');

    return snippetSchemaPromise.catch((error) => {
        expect(generalMetadataStore.loadMetadata).toBeCalledWith('form', 'snippets');
        expect(error.toString()).toEqual(expect.stringContaining('requires a type'));
        expect(error.toString()).toEqual(expect.stringContaining('"snippets"'));
        done();
    });
});

test('Throw if a type is omitted when loading the JSON Schema, but the given reosurceKey has type support', (done) => {
    const snippetMetadata = {
        types: {
            sidebar: {
                schema: {
                    title: {},
                    description: {},
                },
            },
        },
    };

    const snippetPromise = Promise.resolve(snippetMetadata);
    generalMetadataStore.loadMetadata.mockReturnValue(snippetPromise);

    const snippetSchemaPromise = metadataStore.getSchema('snippets');

    return snippetSchemaPromise.catch((error) => {
        expect(generalMetadataStore.loadMetadata).toBeCalledWith('form', 'snippets');
        expect(error.toString()).toEqual(expect.stringContaining('requires a type'));
        expect(error.toString()).toEqual(expect.stringContaining('"snippets"'));
        done();
    });
});

test('Throw exception if no form fields for given resourceKey are available', () => {
    const contactMetadata = {};
    const contactPromise = Promise.resolve(contactMetadata);

    generalMetadataStore.loadMetadata.mockReturnValue(contactPromise);

    return metadataStore.getSchema('contacts').catch((error) => {
        expect(generalMetadataStore.loadMetadata).toBeCalledWith('form', 'contacts');
        expect(error.toString()).toEqual(expect.stringContaining('"contacts"'));
    });
});

test('Throw exception if no schema for given resourceKey are available', () => {
    const contactMetadata = {};
    const contactPromise = Promise.resolve(contactMetadata);

    generalMetadataStore.loadMetadata.mockReturnValue(contactPromise);

    return metadataStore.getJsonSchema('contacts').catch((error) => {
        expect(generalMetadataStore.loadMetadata).toBeCalledWith('form', 'contacts');
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

    generalMetadataStore.loadMetadata.mockReturnValue(snippetPromise);

    return metadataStore.getSchema('snippets', 'default').catch((error) => {
        expect(generalMetadataStore.loadMetadata).toBeCalledWith('form', 'snippets');
        expect(error.toString()).toEqual(expect.stringContaining('no form schema'));
        expect(error.toString()).toEqual(expect.stringContaining('"snippets"'));
        expect(error.toString()).toEqual(expect.stringContaining('"default"'));
    });
});

test('Throw exception if no form fields for given resourceKey and type are available', () => {
    const snippetMetadata = {
        types: {
            default: {},
        },
    };
    const snippetPromise = Promise.resolve(snippetMetadata);

    generalMetadataStore.loadMetadata.mockReturnValue(snippetPromise);

    return metadataStore.getJsonSchema('snippets', 'default').catch((error) => {
        expect(generalMetadataStore.loadMetadata).toBeCalledWith('form', 'snippets');
        expect(error.toString()).toEqual(expect.stringContaining('no json schema'));
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
    generalMetadataStore.loadMetadata.mockReturnValue(snippetPromise);

    const snippetTypesPromise = metadataStore.getSchemaTypes('snippets');
    expect(generalMetadataStore.loadMetadata).toBeCalledWith('form', 'snippets');

    return snippetTypesPromise.then((snippetTypes) => {
        expect(snippetTypes).toMatchSnapshot();
    });
});

test('Return empty object as available types for given resourceKey if types are not supported', () => {
    const snippetMetadata = {
        form: {},
    };
    const snippetPromise = Promise.resolve(snippetMetadata);
    generalMetadataStore.loadMetadata.mockReturnValue(snippetPromise);

    const snippetTypesPromise = metadataStore.getSchemaTypes('snippets');
    expect(generalMetadataStore.loadMetadata).toBeCalledWith('form', 'snippets');

    return snippetTypesPromise.then((snippetTypes) => {
        expect(snippetTypes).toEqual({});
    });
});
