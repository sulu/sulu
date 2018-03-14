/* eslint-disable flowtype/require-valid-file-annotation */
import {observable, when} from 'mobx';
import ResourceRequester from 'sulu-admin-bundle/services/ResourceRequester';
import CollectionStore from '../CollectionStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn(),
}));

test('Do not send request without defined collectionId', () => {
    const locale = observable.box();
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
        },
    }));

    const locale = observable.box('en');
    const collectionStore = new CollectionStore(1, locale);

    when(
        () => !collectionStore.loading,
        () => {
            expect(collectionStore.parentId).toBe(1);
            collectionStore.destroy();
            done();
        }
    );
});
