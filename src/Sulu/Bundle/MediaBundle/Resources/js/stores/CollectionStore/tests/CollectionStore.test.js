/* eslint-disable flowtype/require-valid-file-annotation */
import {observable, when} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import CollectionStore from '../CollectionStore';

jest.mock('sulu-admin-bundle/services', () => ({
    ResourceRequester: {
        get: jest.fn(),
    },
}));

test('Do not send request without defined collectionId', () => {
    const locale = observable();
    new CollectionStore(undefined, locale);

    expect(ResourceRequester.get).not.toBeCalled();
});

test('After loading the collection info should be set', (done) => {
    const Promise = require.requireActual('promise');

    ResourceRequester.get.mockReturnValue(Promise.resolve({
        id: 2,
        title: 'test',
        _embedded: {
            parent: {
                id: 1,
            },
            breadcrumb: [
                {
                    id: 1,
                    title: 'test 1',
                },
            ],
        },
    }));

    const locale = observable();
    const collectionStore = new CollectionStore(1, locale);

    when(
        () => !collectionStore.loading,
        () => {
            expect(collectionStore.collection.parentId).toBe(1);
            expect(collectionStore.collection.breadcrumb.toJS()).toEqual([
                {
                    id: 1,
                    title: 'test 1',
                },
                {
                    id: 2,
                    title: 'test',
                },
            ]);
            collectionStore.destroy();
            done();
        }
    );
});
