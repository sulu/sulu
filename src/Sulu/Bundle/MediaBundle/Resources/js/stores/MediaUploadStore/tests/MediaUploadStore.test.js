// @flow
import 'url-search-params-polyfill';
import {observable, when} from 'mobx';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import MediaUploadStore from '../MediaUploadStore';

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceMetadataStore: {
        getEndpoint: jest.fn((resourceKey) => {
            switch (resourceKey) {
                case 'media':
                    return '/media';
            }
        }),
    },
    ResourceStore: jest.fn(function(resourceKey, id, observableOptions) {
        this.resourceKey = resourceKey;
        this.id = id;
        this.locale = observableOptions ? observableOptions.locale : undefined;
        this.setMultiple = jest.fn();
    }),
}));

test('Calling the "update" method should make a "POST" request to the media update api', () => {
    const openSpy = jest.fn();

    window.XMLHttpRequest = jest.fn(function() {
        this.open = openSpy;
        this.onload = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media', 1, {locale: observable.box('en')}));
    const fileData = new File([''], 'fileName');

    mediaUploadStore.update(fileData);
    expect(openSpy).toBeCalledWith('POST', '/media/1?action=new-version&locale=en');
});

test('Calling the "create" method should make a "POST" request to the media update api', () => {
    const openSpy = jest.fn();

    window.XMLHttpRequest = jest.fn(function() {
        this.open = openSpy;
        this.onload = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const mediaUploadStore = new MediaUploadStore(
        new ResourceStore('media', undefined, {locale: observable.box('en')})
    );
    const fileData = new File([''], 'fileName');

    mediaUploadStore.create(1, fileData);
    expect(openSpy).toBeCalledWith('POST', '/media?locale=en&collection=1');
});

test('After the request was successful the progress will be reset', (done) => {
    window.XMLHttpRequest = jest.fn(function() {
        this.open = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const resourceStore = new ResourceStore('media', 1, {locale: observable.box('en')});
    const mediaUploadStore = new MediaUploadStore(resourceStore);
    const fileData = new File([''], 'fileName');

    mediaUploadStore.update(fileData);
    mediaUploadStore.progress = 4;
    expect(mediaUploadStore.uploading).toEqual(true);

    when(
        () => !mediaUploadStore.uploading,
        (): void => {
            expect(mediaUploadStore.uploading).toBe(false);
            expect(mediaUploadStore.progress).toBe(0);
            expect(resourceStore.setMultiple).toBeCalledWith({});
            done();
        }
    );

    window.XMLHttpRequest.mock.instances[0].onload({ target: {response: '{}'} });
});

test('Should return thumbnail path if available', () => {
    const thumbnailUrl = '/media/uploads/400x400/test.png';
    const resourceStore = new ResourceStore('media', 1, {locale: observable.box('en')});
    resourceStore.data = {
        thumbnails: {
            'sulu-400x400-inset': thumbnailUrl,
        },
    };
    const mediaUploadStore = new MediaUploadStore(resourceStore);

    expect(mediaUploadStore.getThumbnail('sulu-400x400-inset')).toEqual(thumbnailUrl);
});

test('Should return undefined if thumbnail is not available yet', () => {
    const resourceStore = new ResourceStore('media', 1, {locale: observable.box('en')});
    resourceStore.data = {};

    const mediaUploadStore = new MediaUploadStore(resourceStore);

    expect(mediaUploadStore.getThumbnail('100x100')).toEqual(undefined);
});

test('Should return the mime type of the media if available', () => {
    const mimeType = 'image/jpg';
    const resourceStore = new ResourceStore('media', 1, {locale: observable.box('en')});
    resourceStore.data = {
        mimeType,
    };
    const mediaUploadStore = new MediaUploadStore(resourceStore);

    expect(mediaUploadStore.mimeType).toEqual(mimeType);
});

test('Should throw an error if locale not available', () => {
    const resourceStore = new ResourceStore('media', 1);
    resourceStore.data = {};
    const mediaUploadStore = new MediaUploadStore(resourceStore);

    expect(() => mediaUploadStore.locale).toThrow(/localized/);
});

test('Should throw an error if passed resourceStore does not load media', () => {
    expect(() => new MediaUploadStore(new ResourceStore('account', 3))).toThrow('"media"');
});
