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

test.each([true, false])('Should have a locked value of %s', (locked, done) => {
    ResourceRequester.get.mockReturnValue(Promise.resolve({
        id: 2,
        title: 'test',
        locked,
    }));

    const locale = observable.box('en');
    const collectionStore = new CollectionStore(1, locale);

    expect(collectionStore.locked).toEqual(false);

    when(
        () => !collectionStore.loading,
        () => {
            expect(collectionStore.locked).toEqual(locked);
            collectionStore.destroy();
            done();
        }
    );
});

test('Should return an empty permission object if still loading', () => {
    const collectionStore = new CollectionStore(1, observable.box('en'));

    expect(collectionStore.permissions).toEqual({});
});

test('Should return an empty permission object if no id was given', () => {
    const collectionStore = new CollectionStore(undefined, observable.box('en'));

    expect(ResourceRequester.get).not.toBeCalled();
    expect(collectionStore.loading).toEqual(false);
    expect(collectionStore.permissions).toEqual({});
});
