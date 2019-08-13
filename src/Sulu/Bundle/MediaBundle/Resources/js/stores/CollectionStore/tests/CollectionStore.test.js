// @flow
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
    // $FlowFixMe
    const Promise = require.requireActual('promise');

    ResourceRequester.get.mockReturnValue(Promise.resolve({
        id: 2,
        title: 'test',
        _embedded: {
            parent: {
                id: 1,
            },
        },
        _permissions: {
            view: true,
            edit: false,
            delete: false,
        },
    }));

    const locale = observable.box('en');
    const collectionStore = new CollectionStore(1, locale);

    when(
        () => !collectionStore.loading,
        () => {
            expect(collectionStore.parentId).toEqual(1);
            expect(collectionStore.permissions).toEqual({view: true, edit: false, delete: false});
            collectionStore.destroy();
            done();
        }
    );
});
