// @flow
import 'url-search-params-polyfill';
import {observable, when} from 'mobx';
import MediaUploadStore from '../MediaUploadStore';

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceMetadataStore: {
        getBaseUrl: jest.fn().mockImplementation((resourceKey) => {
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

    const locale = observable('en');
    const mediaUploadStore = new MediaUploadStore(locale);
    const testId = 1;
    const fileData = new File([''], 'fileName');

    mediaUploadStore.update(testId, fileData);
    expect(openSpy).toBeCalledWith('POST', '/media/1?action=new-version&locale=en');
});

test('After the request was successful the progress will be reset', (done) => {
    window.XMLHttpRequest = jest.fn(function() {
        this.open = jest.fn();
        this.onerror = jest.fn();
        this.upload = jest.fn();
        this.send = jest.fn();
    });

    const locale = observable('en');
    const mediaUploadStore = new MediaUploadStore(locale);
    const testId = 1;
    const fileData = new File([''], 'fileName');

    mediaUploadStore.update(testId, fileData);

    when(
        () => mediaUploadStore.progress === 0,
        () => {
            expect(mediaUploadStore.uploading).toBe(false);
            expect(mediaUploadStore.progress).toBe(0);
            done();
        }
    );

    window.XMLHttpRequest.mock.instances[0].onload({ target: {response: '{}'} });
});
