// @flow
import metadataStore from '../MetadataStore';
import Requester from '../../../services/Requester';

jest.mock('../../../services/Requester', () => ({
    get: jest.fn(),
}));

test('Load metadata for given type and key', () => {
    metadataStore.endpoint = '/metadata/:type/:key';

    const snippetMetadata = {
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
            case '/metadata/form/snippets':
                return snippetPromise;
            case '/metadata/list/tags':
                return tagPromise;
        }
    });

    expect(metadataStore.loadMetadata('form', 'snippets')).toEqual(snippetPromise);
    expect(metadataStore.loadMetadata('list', 'tags')).toEqual(tagPromise);

    return Promise.all([snippetPromise, tagPromise]).then(([snippetMetadataFromPromise, tagMetadataFromPromise]) => {
        expect(snippetMetadataFromPromise).toBe(snippetMetadata);
        expect(tagMetadataFromPromise).toBe(tagMetadata);
    });
});

test('Load configuration twice should return the same promise', () => {
    const snippetMetadata = {};
    const snippetPromise = Promise.resolve(snippetMetadata);

    Requester.get.mockReturnValue(snippetPromise);

    const snippetPromise1 = metadataStore.loadMetadata('form', 'snippets');
    const snippetPromise2 = metadataStore.loadMetadata('form', 'snippets');

    expect(snippetPromise1).toBe(snippetPromise2);

    return Promise.all([snippetPromise1, snippetPromise2]).then(([snippetMetadata1, snippetMetadata2]) => {
        expect(snippetMetadata1).toBe(snippetMetadata2);
    });
});
