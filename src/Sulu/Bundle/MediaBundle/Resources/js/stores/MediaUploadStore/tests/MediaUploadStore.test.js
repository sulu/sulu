// @flow
import 'url-search-params-polyfill';
import {observable, when} from 'mobx';
import {resourceRouteRegistry, ResourceRequester} from 'sulu-admin-bundle/services';
import MediaUploadStore from '../MediaUploadStore';

jest.mock('sulu-admin-bundle/services', () => ({
    ResourceRequester: {
        delete: jest.fn(),
    },
    resourceRouteRegistry: {
        getDetailUrl: jest.fn(),
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
    resourceRouteRegistry.getDetailUrl.mockReturnValue('/media/1?action=new-version&locale=en');

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
    expect(resourceRouteRegistry.getDetailUrl).toBeCalledWith(
        'media',
        {action: 'new-version', id: 1, locale: 'en'}
    );
    expect(openSpy).toBeCalledWith('POST', '/media/1?action=new-version&locale=en');
});

test('Calling the "create" method should make a "POST" request to the media update api', () => {
    resourceRouteRegistry.getDetailUrl.mockReturnValue('/media?locale=en&collection=1');

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

    const createPromise = mediaUploadStore.create(1, fileData);

    expect(resourceRouteRegistry.getDetailUrl).toBeCalledWith('media', {collection: 1, locale: 'en'});
    expect(openSpy).toBeCalledWith('POST', '/media?locale=en&collection=1');

    window.XMLHttpRequest.mock.instances[0].onload({target: {response: '{"title": "test1"}'}});

    return createPromise.then(() => {
        expect(mediaUploadStore.media).toEqual({title: 'test1'});
    });
});

test('Calling "delete" method should call the "delete" method of the ResourceRequester', () => {
    const mediaUploadStore = new MediaUploadStore(
        {id: 2, mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url: ''},
        observable.box('en')
    );

    ResourceRequester.delete.mockReturnValue(Promise.resolve());

    const deletePromise = mediaUploadStore.delete();
    expect(ResourceRequester.delete).toBeCalledWith('media', {id: 2});

    return deletePromise.then(() => {
        expect(mediaUploadStore.media).toEqual(undefined);
    });
});

test('Calling "deletePreviewImage" method should call the "delete" method of the ResourceRequester', () => {
    const mediaUploadStore = new MediaUploadStore(
        {id: 2, mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url: ''},
        observable.box('en')
    );

    const media = {id: 2, mimeType: 'image/jpeg', title: 'test', thumbnails: {'50x50': 'image.jpg'}, url: ''};
    ResourceRequester.delete.mockReturnValue(Promise.resolve(media));

    const deletePromise = mediaUploadStore.deletePreviewImage();
    expect(ResourceRequester.delete).toBeCalledWith('media_preview', {id: 2});

    return deletePromise.then(() => {
        expect(mediaUploadStore.media).toEqual(media);
    });
});

test('Calling the "updatePreviewImage" method should make a "POST" request to the preview media update api', () => {
    resourceRouteRegistry.getDetailUrl.mockReturnValue('/media/1/preview?locale=en');

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

    mediaUploadStore.updatePreviewImage(fileData);
    expect(resourceRouteRegistry.getDetailUrl).toBeCalledWith(
        'media_preview',
        {id: 1, locale: 'en'}
    );
    expect(openSpy).toBeCalledWith('POST', '/media/1/preview?locale=en');
});

test('After the "update" call request was successful the progress will be reset', (done) => {
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
            expect(mediaUploadStore.media)
                .toEqual({id: 1, mimeType: 'image/jpeg', title: 'test1', thumbnails: {}, url: ''});
            done();
        }
    );

    window.XMLHttpRequest.mock.instances[0].onload({target: {response: '{"title": "test1"}'}});
});

test('After the "updatePreviewImage" call request was successful the progress will be reset', (done) => {
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

    mediaUploadStore.updatePreviewImage(fileData);
    mediaUploadStore.progress = 4;
    expect(mediaUploadStore.uploading).toEqual(true);

    when(
        () => !mediaUploadStore.uploading,
        (): void => {
            expect(mediaUploadStore.uploading).toEqual(false);
            expect(mediaUploadStore.progress).toEqual(0);
            expect(mediaUploadStore.media).toEqual({
                id: 1,
                mimeType: 'image/jpeg',
                title: 'test',
                thumbnails: {'50x50': 'image.jpg'},
                url: '',
            });
            done();
        }
    );

    const response =
        '{"id": 1, "mimeType": "image/jpeg", "title": "test", "thumbnails": {"50x50": "image.jpg"}, "url": ""}';

    window.XMLHttpRequest.mock.instances[0].onload({
        target: {
            response,
        },
    });
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
