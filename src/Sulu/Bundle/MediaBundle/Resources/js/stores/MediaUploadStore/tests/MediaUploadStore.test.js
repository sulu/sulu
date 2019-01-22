// @flow
import 'url-search-params-polyfill';
import {observable, when} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {buildQueryString} from 'sulu-admin-bundle/utils';
import MediaUploadStore from '../MediaUploadStore';

jest.mock('sulu-admin-bundle/utils', () => ({
    buildQueryString: jest.fn(),
}));

jest.mock('sulu-admin-bundle/services', () => ({
    ResourceRequester: {
        delete: jest.fn(),
    },
    resourceEndpointRegistry: {
        getEndpoint: jest.fn((resourceKey) => {
            switch (resourceKey) {
                case 'media':
                    return '/media';
            }
        }),
    },
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id, observableOptions) {
        this.resourceKey = resourceKey;
        this.id = id;
        this.data = {id};
        this.locale = observableOptions ? observableOptions.locale : undefined;
        this.setMultiple = jest.fn();
        this.delete = jest.fn();
    }),
}));

test('Calling the "update" method should make a "POST" request to the media update api', () => {
    // $FlowFixMe
    buildQueryString.mockReturnValueOnce('?action=new-version&locale=en');

    const openSpy = jest.fn();

    window.XMLHttpRequest = jest.fn(function() {
        this.open = openSpy;
        this.onload = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(
        {id: 1, mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url: ''},
        observable.box('en')
    );
    const fileData = new File([''], 'fileName');

    mediaUploadStore.update(fileData);
    expect(buildQueryString).toBeCalledWith({action: 'new-version', locale: 'en'});
    expect(openSpy).toBeCalledWith('POST', '/media/1?action=new-version&locale=en');
});

test('Calling the "create" method should make a "POST" request to the media update api', () => {
    // $FlowFixMe
    buildQueryString.mockReturnValueOnce('?locale=en&collection=1');

    const openSpy = jest.fn();

    window.XMLHttpRequest = jest.fn(function() {
        this.open = openSpy;
        this.onload = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );
    const fileData = new File([''], 'fileName');

    mediaUploadStore.create(1, fileData);
    expect(buildQueryString).toBeCalledWith({collection: 1, locale: 'en'});
    expect(openSpy).toBeCalledWith('POST', '/media?locale=en&collection=1');
});

test('Calling "delete" method should call the "delete" method of the ResourceRequester', () => {
    const mediaUploadStore = new MediaUploadStore(
        {id: 2, mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url: ''},
        observable.box('en')
    );

    ResourceRequester.delete.mockReturnValue(Promise.resolve());

    const deletePromise = mediaUploadStore.delete();
    expect(ResourceRequester.delete).toBeCalledWith('media', 2);

    return deletePromise.then(() => {
        expect(mediaUploadStore.media).toEqual(undefined);
    });
});

test('After the request was successful the progress will be reset', (done) => {
    window.XMLHttpRequest = jest.fn(function() {
        this.open = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(
        {id: 1, mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url: ''},
        observable.box('en')
    );
    const fileData = new File([''], 'fileName');

    mediaUploadStore.update(fileData);
    mediaUploadStore.progress = 4;
    expect(mediaUploadStore.uploading).toEqual(true);

    when(
        () => !mediaUploadStore.uploading,
        (): void => {
            expect(mediaUploadStore.uploading).toEqual(false);
            expect(mediaUploadStore.progress).toEqual(0);
            expect(mediaUploadStore.media).toEqual({});
            done();
        }
    );

    window.XMLHttpRequest.mock.instances[0].onload({target: {response: '{}'}});
});

test('Should return thumbnail path if available', () => {
    const thumbnailUrl = '/media/uploads/400x400/test.png';
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {
                'sulu-400x400-inset': thumbnailUrl,
            },
            url: '',
        },
        observable.box('en')
    );

    expect(mediaUploadStore.getThumbnail('sulu-400x400-inset')).toEqual(thumbnailUrl);
});

test('Should return undefined if thumbnail is not available yet', () => {
    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );

    expect(mediaUploadStore.getThumbnail('100x100')).toEqual(undefined);
});

test('Should return the mime type of the media if available', () => {
    const mimeType = 'image/jpg';
    const mediaUploadStore = new MediaUploadStore(
        {id: 1, mimeType, title: 'test', thumbnails: {}, url: ''},
        observable.box('en')
    );

    expect(mediaUploadStore.mimeType).toEqual(mimeType);
});

test('Should return undefined if the mime type is not available yet', () => {
    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );

    expect(mediaUploadStore.mimeType).toEqual(undefined);
});

test('Should return downloadUrl if available', () => {
    const url = 'test.jpg';
    const mediaUploadStore = new MediaUploadStore(
        {id: 1, mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url},
        observable.box('en')
    );

    expect(mediaUploadStore.downloadUrl).toEqual(url);
});

test('Should return undefined if downloadUrl is not available', () => {
    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );

    expect(mediaUploadStore.downloadUrl).toEqual(undefined);
});
