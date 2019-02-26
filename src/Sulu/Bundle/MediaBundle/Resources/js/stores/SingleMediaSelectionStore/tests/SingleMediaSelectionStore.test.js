// @flow
import {observable, toJS} from 'mobx';
import ResourceRequester from 'sulu-admin-bundle/services/ResourceRequester';
import SingleMediaSelectionStore from '../SingleMediaSelectionStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({})),
}));

beforeEach(() => {
    jest.resetAllMocks();
});

test('Should load media when being constructed', () => {
    const getPromise = Promise.resolve({
        id: 22,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });
    ResourceRequester.get.mockReturnValue(getPromise);

    const singleMediaSelectionStore = new SingleMediaSelectionStore(22, observable.box('en'));

    expect(ResourceRequester.get).toBeCalledWith(
        'media',
        {
            id: 22,
            locale: 'en',
        }
    );

    return getPromise.then(() => {
        expect(singleMediaSelectionStore.selectedMedia).toEqual({
            id: 22,
            title: 'test media',
            mimeType: 'image/jpeg',
            thumbnails: {
                'sulu-25x25': '/images/25x25/awesome.png',
            },
        });
        expect(singleMediaSelectionStore.selectedMediaId).toEqual(22);
    });
});

test('Should not make a request when being constructed with undefined', () => {
    const singleMediaSelectionStore = new SingleMediaSelectionStore(undefined, observable.box('en'));

    expect(ResourceRequester.get).not.toHaveBeenCalled();
    expect(singleMediaSelectionStore.selectedMedia).toBeUndefined();
});

test('Should set selected-media', () => {
    const singleMediaSelectionStore = new SingleMediaSelectionStore(undefined, observable.box('en'));

    singleMediaSelectionStore.set({
        id: 33,
        title: 'test media',
        mimeType: 'image/jpeg',
        url: '',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    expect(toJS(singleMediaSelectionStore.selectedMedia)).toEqual({
        id: 33,
        title: 'test media',
        mimeType: 'image/jpeg',
        url: '',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });
    expect(singleMediaSelectionStore.selectedMediaId).toEqual(33);
});

test('Should clear selected-media', () => {
    const singleMediaSelectionStore = new SingleMediaSelectionStore(undefined, observable.box('en'));

    singleMediaSelectionStore.set({
        id: 33,
        title: 'test media',
        mimeType: 'image/jpeg',
        url: '',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    expect(singleMediaSelectionStore.selectedMediaId).toEqual(33);

    singleMediaSelectionStore.clear();

    expect(toJS(singleMediaSelectionStore.selectedMedia)).toEqual(undefined);
    expect(singleMediaSelectionStore.selectedMediaId).toEqual(undefined);
});

test('Should load media with given id', () => {
    const singleMediaSelectionStore = new SingleMediaSelectionStore(undefined, observable.box('en'));

    const getPromise = Promise.resolve({
        id: 22,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });
    ResourceRequester.get.mockReturnValue(getPromise);

    singleMediaSelectionStore.loadSelectedMedia(22, observable.box('en'));

    expect(ResourceRequester.get).toBeCalledWith(
        'media',
        {
            id: 22,
            locale: 'en',
        }
    );

    return getPromise.then(() => {
        expect(singleMediaSelectionStore.selectedMedia).toEqual(expect.objectContaining({
            id: 22,
            title: 'test media',
            mimeType: 'image/jpeg',
            thumbnails: {
                'sulu-25x25': '/images/25x25/awesome.png',
            },
        }));
        expect(singleMediaSelectionStore.selectedMediaId).toEqual(22);
    });
});
