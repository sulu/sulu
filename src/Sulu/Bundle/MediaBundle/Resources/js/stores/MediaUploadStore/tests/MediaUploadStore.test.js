// @flow
import 'url-search-params-polyfill';
import {observable, when} from 'mobx';
import MediaUploadStore from '../MediaUploadStore';

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceMetadataStore: {
        getEndpoint: jest.fn().mockImplementation((resourceKey) => {
            switch (resourceKey) {
                case 'media':
                    return '/media';
            }
        }),
    },
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

    const locale = observable.box('en');
    const mediaUploadStore = new MediaUploadStore(locale);
    const testMediaId = 1;
    const fileData = new File([''], 'fileName');

    mediaUploadStore.update(testMediaId, fileData);
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

    const locale = observable.box('en');
    const mediaUploadStore = new MediaUploadStore(locale);
    const testCollectionId = 1;
    const fileData = new File([''], 'fileName');

    mediaUploadStore.create(testCollectionId, fileData);
    expect(openSpy).toBeCalledWith('POST', '/media?locale=en&collection=1');
});

test('After the request was successful the progress will be reset', (done) => {
    window.XMLHttpRequest = jest.fn(function() {
        this.open = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const locale = observable.box('en');
    const mediaUploadStore = new MediaUploadStore(locale);
    const testId = 1;
    const fileData = new File([''], 'fileName');

    mediaUploadStore.update(testId, fileData);

    when(
        () => 0 === mediaUploadStore.progress,
        (): void => {
            expect(mediaUploadStore.uploading).toBe(false);
            expect(mediaUploadStore.progress).toBe(0);
            done();
        }
    );

    window.XMLHttpRequest.mock.instances[0].onload({ target: {response: '{}'} });
});
