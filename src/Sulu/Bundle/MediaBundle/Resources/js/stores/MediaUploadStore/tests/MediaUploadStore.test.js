/* eslint-disable flowtype/require-valid-file-annotation */
import 'url-search-params-polyfill';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import MediaUploadStore from '../MediaUploadStore';

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.locale = {
            get: jest.fn().mockReturnValue('en'),
        };
        this.data = {
            id: 1,
            thumbnails: {
                'sulu-400x400': '/admin/assets/400/400',
            },
        };
    }),
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

    const resourceStore = new ResourceStore();
    const mediaUploadStore = new MediaUploadStore(resourceStore);
    const testId = 1;
    const fileData = {};

    mediaUploadStore.update(testId, fileData);
    expect(openSpy).toBeCalledWith('POST', '/media/1?action=new-version&locale=en');
});

test('Calling "setUploading" with "false" will reset the progress', (done) => {
    const resourceStore = new ResourceStore();
    const mediaUploadStore = new MediaUploadStore(resourceStore);

    mediaUploadStore.setUploading(true);
    mediaUploadStore.setProgress(50);
    expect(mediaUploadStore.uploading).toBe(true);

    mediaUploadStore.setUploading(false);
    expect(mediaUploadStore.uploading).toBe(false);

    setTimeout(() => {
        expect(mediaUploadStore.progress).toBe(0);
        done();
    }, 1001);
});

test('The "source" property of the MediaUploadStore should return the thumbnail url', () => {
    const resourceStore = new ResourceStore();
    const mediaUploadStore = new MediaUploadStore(resourceStore);

    expect(mediaUploadStore.source).toBe(`${window.location.origin}/admin/assets/400/400`);
});
