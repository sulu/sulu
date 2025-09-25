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
            snippet_areas: [
                {
                    snippetTitle: 'Default 1',
                    snippetUuid: 'some-uuid-1',
                    key: 'default',
                    template: 'default',
                    title: 'Default',
                },
                {
                    snippetTitle: 'Footer 1',
                    snippetUuid: 'some-uuid-2',
                    key: 'footer',
                    template: 'footer',
                    title: 'Footer',
                },
            ],
        },
    });
    ResourceRequester.getList.mockReturnValue(listPromise);

    const snippetAreaStore = new SnippetAreaStore('sulu');

    expect(ResourceRequester.getList).toBeCalledWith('snippet_areas', {webspaceKey: 'sulu'});

    expect(snippetAreaStore.loading).toEqual(true);
    expect(snippetAreaStore.snippetAreas).toEqual({});

    return listPromise.then(() => {
        expect(snippetAreaStore.loading).toEqual(false);
        expect(snippetAreaStore.snippetAreas).toEqual({
            default: {
                snippetTitle: 'Default 1',
                snippetUuid: 'some-uuid-1',
                key: 'default',
                template: 'default',
                title: 'Default',
            },
            footer: {
                snippetTitle: 'Footer 1',
                snippetUuid: 'some-uuid-2',
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
            snippet_areas: [
                {
                    snippetTitle: 'Default 1',
                    snippetUuid: 'some-uuid-1',
                    key: 'default',
                    template: 'default',
                    title: 'Default',
                },
                {
                    snippetTitle: 'Footer 1',
                    snippetUuid: 'some-uuid-2',
                    key: 'footer',
                    template: 'footer',
                    title: 'Footer',
                },
            ],
        },
    });
    ResourceRequester.getList.mockReturnValue(listPromise);

    const putPromise = Promise.resolve({
        snippetTitle: 'Footer 2',
        snippetUuid: 'some-uuid-3',
        key: 'footer',
        template: 'footer',
        title: 'Footer',
    });
    ResourceRequester.put.mockReturnValue(putPromise);

    const snippetAreaStore = new SnippetAreaStore('sulu');
    snippetAreaStore.save('footer', 'some-uuid-3');

    expect(ResourceRequester.put)
        .toBeCalledWith('snippet_areas', {snippetUuid: 'some-uuid-3'}, {key: 'footer', webspaceKey: 'sulu'});

    expect(snippetAreaStore.saving).toEqual(true);

    return listPromise.then(() => {
        expect(snippetAreaStore.saving).toEqual(false);
        expect(snippetAreaStore.snippetAreas).toEqual({
            default: {
                snippetTitle: 'Default 1',
                snippetUuid: 'some-uuid-1',
                key: 'default',
                template: 'default',
                title: 'Default',
            },
            footer: {
                snippetTitle: 'Footer 2',
                snippetUuid: 'some-uuid-3',
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
            snippet_areas: [
                {
                    snippetTitle: 'Default 1',
                    snippetUuid: 'some-uuid-1',
                    key: 'default',
                    template: 'default',
                    title: 'Default',
                },
                {
                    snippetTitle: 'Footer 1',
                    snippetUuid: 'some-uuid-2',
                    key: 'footer',
                    template: 'footer',
                    title: 'Footer',
                },
            ],
        },
    });
    ResourceRequester.getList.mockReturnValue(listPromise);

    const deletePromise = Promise.resolve({
        snippetTitle: null,
        snippetUuid: null,
        key: 'footer',
        template: 'footer',
        title: 'Footer',
    });
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const snippetAreaStore = new SnippetAreaStore('sulu');
    snippetAreaStore.delete('footer');

    expect(ResourceRequester.delete)
        .toBeCalledWith('snippet_areas', {key: 'footer', webspaceKey: 'sulu'});

    expect(snippetAreaStore.deleting).toEqual(true);

    return listPromise.then(() => {
        expect(snippetAreaStore.deleting).toEqual(false);
        expect(snippetAreaStore.snippetAreas).toEqual({
            default: {
                snippetTitle: 'Default 1',
                snippetUuid: 'some-uuid-1',
                key: 'default',
                template: 'default',
                title: 'Default',
            },
            footer: {
                snippetTitle: null,
                snippetUuid: null,
                key: 'footer',
                template: 'footer',
                title: 'Footer',
            },
        });
    });
});
