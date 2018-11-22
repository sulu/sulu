// @flow
import {observable, toJS} from 'mobx';
import ResourceRequester from 'sulu-admin-bundle/services/ResourceRequester';
import MediaSelectionStore from '../MediaSelectionStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

test('Should not make a request if the given ids array is empty or undefined', () => {
    const Promise = jest.requireActual('promise');

    ResourceRequester.getList.mockReturnValue(Promise.resolve());

    const mediaIds = [];

    new MediaSelectionStore(mediaIds, observable.box('en'));
    new MediaSelectionStore(null, observable.box('en'));

    expect(ResourceRequester.getList).not.toBeCalled();
});

test('Should prepare media data and store it inside an array', () => {
    const mediaSelectionStore = new MediaSelectionStore(null, observable.box('en'));

    mediaSelectionStore.add({
        id: 1,
        title: 'Awesome',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    expect(mediaSelectionStore.selectedMediaIds).toEqual([1]);
    expect(toJS(mediaSelectionStore.selectedMedia)).toEqual([
        {
            id: 1,
            title: 'Awesome',
            thumbnail: '/images/25x25/awesome.png',
        },
    ]);
});

test('Should remove media from array', () => {
    const mediaSelectionStore = new MediaSelectionStore(null, observable.box('en'));

    mediaSelectionStore.add({
        id: 1,
        title: 'Awesome 1',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    mediaSelectionStore.add({
        id: 2,
        title: 'Awesome 2',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    mediaSelectionStore.removeById(1);
    expect(mediaSelectionStore.selectedMediaIds).toEqual([2]);
    expect(toJS(mediaSelectionStore.selectedMedia)).toEqual([
        {
            id: 2,
            title: 'Awesome 2',
            thumbnail: '/images/25x25/awesome.png',
        },
    ]);

    mediaSelectionStore.removeById(2);
    expect(mediaSelectionStore.selectedMediaIds).toEqual([]);
    expect(toJS(mediaSelectionStore.selectedMedia)).toEqual([]);
});

test('Should move the media positions inside the array', () => {
    const mediaSelectionStore = new MediaSelectionStore(null, observable.box('en'));

    mediaSelectionStore.add({
        id: 1,
        title: 'Awesome 1',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    mediaSelectionStore.add({
        id: 2,
        title: 'Awesome 2',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    mediaSelectionStore.add({
        id: 3,
        title: 'Awesome 3',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    expect(mediaSelectionStore.selectedMediaIds).toEqual([1, 2, 3]);
    mediaSelectionStore.move(0, 2);
    expect(mediaSelectionStore.selectedMediaIds).toEqual([2, 3, 1]);
    expect(toJS(mediaSelectionStore.selectedMedia)).toEqual([
        {
            id: 2,
            title: 'Awesome 2',
            thumbnail: '/images/25x25/awesome.png',
        },
        {
            id: 3,
            title: 'Awesome 3',
            thumbnail: '/images/25x25/awesome.png',
        },
        {
            id: 1,
            title: 'Awesome 1',
            thumbnail: '/images/25x25/awesome.png',
        },
    ]);
});
