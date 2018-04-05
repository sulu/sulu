// @flow
import resourceMetadataStore from '../ResourceMetadataStore';
import Requester from '../../../services/Requester';

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

test('Load configuration for given key', () => {
    const snippetMetadata = {
        list: {
            id: {},
            title: {},
            template: {},
            changed: {},
            created: {},
        },
        form: {
            title: {
                label: 'Title',
                type: 'text_line',
            },
            slogan: {
                label: 'Slogan',
                type: 'text_line',
            },
            media: {
                label: 'Media',
                type: 'media_selection',
            },
        },
    };

    const tagMetadata = {
        list: {
            id: {},
            name: {},
        },
        form: {
            name: {
                label: 'Name',
                type: 'text_line',
            },
        },
    };

    const snippetPromise = Promise.resolve(snippetMetadata);
    const tagPromise = Promise.resolve(tagMetadata);

    Requester.get.mockImplementation((key) => {
        switch (key) {
            case '/admin/resources/snippets':
                return snippetPromise;
            case '/admin/resources/tags':
                return tagPromise;
        }
    });

    expect(resourceMetadataStore.loadConfiguration('snippets')).toEqual(snippetPromise);
    expect(resourceMetadataStore.loadConfiguration('tags')).toEqual(tagPromise);

    return Promise.all([snippetPromise, tagPromise]).then(([snippetMetadataFromPromise, tagMetadataFromPromise]) => {
        // check if promises have been cached
        expect(resourceMetadataStore.configurationPromises.snippets).toEqual(snippetPromise);
        expect(resourceMetadataStore.configurationPromises.tags).toEqual(tagPromise);

        expect(snippetMetadataFromPromise).toBe(snippetMetadata);
        expect(tagMetadataFromPromise).toBe(tagMetadata);
    });
});

test('Load configuration twice should return the same promise', () => {
    const snippetMetadata = {};
    const snippetPromise = Promise.resolve(snippetMetadata);

    Requester.get.mockReturnValue(snippetPromise);

    const snippetPromise1 = resourceMetadataStore.loadConfiguration('snippets');
    const snippetPromise2 = resourceMetadataStore.loadConfiguration('snippets');

    expect(snippetPromise1).toBe(snippetPromise2);

    return Promise.all([snippetPromise1, snippetPromise2]).then(([snippetMetadata1, snippetMetadata2]) => {
        expect(snippetMetadata1).toBe(snippetMetadata2);
    });
});

test('Throw exception when loading configuration for not existing key', () => {
    Requester.get.mockImplementation(() => {throw new Error();});
    expect(() => resourceMetadataStore.loadConfiguration('not-existing')).toThrow();
});
