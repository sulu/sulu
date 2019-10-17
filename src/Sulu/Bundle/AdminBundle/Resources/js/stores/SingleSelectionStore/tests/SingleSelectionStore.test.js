// @flow
import {observable, toJS} from 'mobx';
import SingleSelectionStore from '../SingleSelectionStore';
import ResourceRequester from '../../../services/ResourceRequester';

jest.mock('../../../services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({})),
}));

test('Should load item when being constructed', () => {
    const getPromise = Promise.resolve({
        id: 1,
    });

    ResourceRequester.get.mockReturnValue(getPromise);

    const singleSelectionStore = new SingleSelectionStore('snippets', 1, observable.box('en'));

    expect(ResourceRequester.get).toBeCalledWith(
        'snippets',
        {
            id: 1,
            locale: 'en',
        }
    );

    return getPromise.then(() => {
        expect(toJS(singleSelectionStore.item)).toEqual({id: 1});
    });
});

test('Should set item to null when 404 is returned', (done) => {
    const getPromise = Promise.reject({
        status: 404,
    });

    ResourceRequester.get.mockReturnValue(getPromise);

    const singleSelectionStore = new SingleSelectionStore('snippets', 1, observable.box('en'));

    expect(ResourceRequester.get).toBeCalledWith(
        'snippets',
        {
            id: 1,
            locale: 'en',
        }
    );

    expect(toJS(singleSelectionStore.loading)).toEqual(true);

    setTimeout(() => {
        expect(toJS(singleSelectionStore.item)).toEqual(null);
        expect(toJS(singleSelectionStore.loading)).toEqual(false);
        done();
    });
});

test('Should load item when being constructed with additional options', () => {
    const getPromise = Promise.resolve({
        id: 1,
    });

    ResourceRequester.get.mockReturnValue(getPromise);

    const singleSelectionStore = new SingleSelectionStore('snippets', 1, observable.box('en'), {test: 'value'});

    expect(ResourceRequester.get).toBeCalledWith(
        'snippets',
        {
            id: 1,
            locale: 'en',
            test: 'value',
        }
    );

    return getPromise.then(() => {
        expect(toJS(singleSelectionStore.item)).toEqual({id: 1});
    });
});

test('Should not load item but replace current selection with undefined if no itemId is given', () => {
    const selectionStore = new SingleSelectionStore('snippets', undefined, observable.box('en'));
    selectionStore.item = {id: 1};

    selectionStore.loadItem(undefined);

    expect(ResourceRequester.get).not.toBeCalled();

    expect(toJS(selectionStore.item)).toEqual(undefined);
});

test('Should load items when being constructed without a locale', () => {
    const getPromise = Promise.resolve({
        id: 1,
    });

    ResourceRequester.get.mockReturnValue(getPromise);

    const singleSelectionStore = new SingleSelectionStore('snippets', 2);

    expect(ResourceRequester.get).toBeCalledWith(
        'snippets',
        {
            id: 2,
            locale: undefined,
        }
    );

    return getPromise.then(() => {
        expect(toJS(singleSelectionStore.item)).toEqual({id: 1});
    });
});

test('Should set all item on the store', () => {
    const singleSelectionStore = new SingleSelectionStore('snippets', undefined, observable.box('en'));

    expect(singleSelectionStore.item).toEqual(undefined);

    singleSelectionStore.set({id: 3});

    expect(singleSelectionStore.item).toEqual({id: 3});
});
