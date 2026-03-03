// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import SnippetAreaStore from '../../stores/SnippetAreaStore';

jest.mock('sulu-admin-bundle/services', () => ({
    ResourceRequester: {
        getList: jest.fn(),
        put: jest.fn(),
        delete: jest.fn(),
    },
}));

test('Load snippet areas when constructing the store', () => {
    const listPromise = Promise.resolve({
        _embedded: {
            areas: [
                {
                    defaultTitle: 'Default 1',
                    defaultUuid: 'some-uuid-1',
                    key: 'default',
                    template: 'default',
                    title: 'Default',
                },
                {
                    defaultTitle: 'Footer 1',
                    defaultUuid: 'some-uuid-2',
                    key: 'footer',
                    template: 'footer',
                    title: 'Footer',
                },
            ],
        },
    });
    ResourceRequester.getList.mockReturnValue(listPromise);

    const snippetAreaStore = new SnippetAreaStore('sulu');

    expect(ResourceRequester.getList).toHaveBeenCalledWith('snippet_areas', {webspace: 'sulu'});

    expect(snippetAreaStore.loading).toEqual(true);
    expect(snippetAreaStore.snippetAreas).toEqual({});

    return listPromise.then(() => {
        expect(snippetAreaStore.loading).toEqual(false);
        expect(snippetAreaStore.snippetAreas).toEqual({
            default: {
                defaultTitle: 'Default 1',
                defaultUuid: 'some-uuid-1',
                key: 'default',
                template: 'default',
                title: 'Default',
            },
            footer: {
                defaultTitle: 'Footer 1',
                defaultUuid: 'some-uuid-2',
                key: 'footer',
                template: 'footer',
                title: 'Footer',
            },
        });
    });
});

test('Save snippet area when save is calld', () => {
    const listPromise = Promise.resolve({
        _embedded: {
            areas: [
                {
                    defaultTitle: 'Default 1',
                    defaultUuid: 'some-uuid-1',
                    key: 'default',
                    template: 'default',
                    title: 'Default',
                },
                {
                    defaultTitle: 'Footer 1',
                    defaultUuid: 'some-uuid-2',
                    key: 'footer',
                    template: 'footer',
                    title: 'Footer',
                },
            ],
        },
    });
    ResourceRequester.getList.mockReturnValue(listPromise);

    const putPromise = Promise.resolve({
        defaultTitle: 'Footer 2',
        defaultUuid: 'some-uuid-3',
        key: 'footer',
        template: 'footer',
        title: 'Footer',
    });
    ResourceRequester.put.mockReturnValue(putPromise);

    const snippetAreaStore = new SnippetAreaStore('sulu');
    snippetAreaStore.save('footer', 'some-uuid-3');

    expect(ResourceRequester.put)
        .toHaveBeenCalledWith('snippet_areas', {defaultUuid: 'some-uuid-3'}, {key: 'footer', webspace: 'sulu'});

    expect(snippetAreaStore.saving).toEqual(true);

    return listPromise.then(() => {
        expect(snippetAreaStore.saving).toEqual(false);
        expect(snippetAreaStore.snippetAreas).toEqual({
            default: {
                defaultTitle: 'Default 1',
                defaultUuid: 'some-uuid-1',
                key: 'default',
                template: 'default',
                title: 'Default',
            },
            footer: {
                defaultTitle: 'Footer 2',
                defaultUuid: 'some-uuid-3',
                key: 'footer',
                template: 'footer',
                title: 'Footer',
            },
        });
    });
});

test('Delete snippet area when delete is calld', () => {
    const listPromise = Promise.resolve({
        _embedded: {
            areas: [
                {
                    defaultTitle: 'Default 1',
                    defaultUuid: 'some-uuid-1',
                    key: 'default',
                    template: 'default',
                    title: 'Default',
                },
                {
                    defaultTitle: 'Footer 1',
                    defaultUuid: 'some-uuid-2',
                    key: 'footer',
                    template: 'footer',
                    title: 'Footer',
                },
            ],
        },
    });
    ResourceRequester.getList.mockReturnValue(listPromise);

    const deletePromise = Promise.resolve({
        defaultTitle: null,
        defaultUuid: null,
        key: 'footer',
        template: 'footer',
        title: 'Footer',
    });
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const snippetAreaStore = new SnippetAreaStore('sulu');
    snippetAreaStore.delete('footer');

    expect(ResourceRequester.delete)
        .toHaveBeenCalledWith('snippet_areas', {key: 'footer', webspace: 'sulu'});

    expect(snippetAreaStore.deleting).toEqual(true);

    return listPromise.then(() => {
        expect(snippetAreaStore.deleting).toEqual(false);
        expect(snippetAreaStore.snippetAreas).toEqual({
            default: {
                defaultTitle: 'Default 1',
                defaultUuid: 'some-uuid-1',
                key: 'default',
                template: 'default',
                title: 'Default',
            },
            footer: {
                defaultTitle: null,
                defaultUuid: null,
                key: 'footer',
                template: 'footer',
                title: 'Footer',
            },
        });
    });
});

test('Handle 403 forbidden error when loading snippet areas', () => {
    const listPromise = Promise.reject({status: 403});
    ResourceRequester.getList.mockReturnValue(listPromise);

    const snippetAreaStore = new SnippetAreaStore('sulu');

    expect(snippetAreaStore.loading).toEqual(true);
    expect(snippetAreaStore.forbidden).toEqual(false);
    expect(snippetAreaStore.unexpectedError).toEqual(false);

    return listPromise.catch(() => {
        // Wait for MobX action to complete
    }).then(() => {
        expect(snippetAreaStore.loading).toEqual(false);
        expect(snippetAreaStore.forbidden).toEqual(true);
        expect(snippetAreaStore.unexpectedError).toEqual(false);
        expect(snippetAreaStore.snippetAreas).toEqual({});
    });
});

test('Handle unexpected error when loading snippet areas', () => {
    const listPromise = Promise.reject({status: 500});
    ResourceRequester.getList.mockReturnValue(listPromise);

    const snippetAreaStore = new SnippetAreaStore('sulu');

    expect(snippetAreaStore.loading).toEqual(true);
    expect(snippetAreaStore.forbidden).toEqual(false);
    expect(snippetAreaStore.unexpectedError).toEqual(false);

    return listPromise.catch(() => {
        // Wait for MobX action to complete
    }).then(() => {
        expect(snippetAreaStore.loading).toEqual(false);
        expect(snippetAreaStore.forbidden).toEqual(false);
        expect(snippetAreaStore.unexpectedError).toEqual(true);
        expect(snippetAreaStore.snippetAreas).toEqual({});
    });
});
