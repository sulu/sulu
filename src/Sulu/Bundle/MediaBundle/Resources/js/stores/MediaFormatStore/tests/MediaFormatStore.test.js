// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import MediaFormatStore from '../MediaFormatStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
    put: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
}));

test('Load media formats', () => {
    const mediaFormats = {
        '300x': {
            cropX: 300,
            cropY: 100,
        },
    };
    const promise = Promise.resolve(mediaFormats);
    ResourceRequester.getList.mockReturnValue(promise);
    const mediaFormatStore = new MediaFormatStore(4, 'de');
    expect(ResourceRequester.getList).toBeCalledWith('media_formats', {id: 4, locale: 'de'});
    expect(mediaFormatStore.loading).toEqual(true);

    return promise.then(() => {
        expect(mediaFormatStore.loading).toEqual(false);
        expect(mediaFormatStore.mediaFormats).toEqual(mediaFormats);
    });
});

test('Update a media format', () => {
    const mediaFormats = {
        '300x': {
            cropX: 300,
            cropY: 100,
        },
        'x300': {
            cropX: 100,
            cropY: 100,
        },
    };
    const listPromise = Promise.resolve(mediaFormats);
    ResourceRequester.getList.mockReturnValue(listPromise);
    const mediaFormatStore = new MediaFormatStore(4, 'de');

    return listPromise.then(() => {
        const cropData = {cropX: 60, cropY: 120, cropHeight: 100, cropWidth: 200};
        const putPromise = Promise.resolve(cropData);
        ResourceRequester.put.mockReturnValue(putPromise);
        mediaFormatStore.updateFormatOptions('x300', cropData);

        expect(mediaFormatStore.saving).toEqual(true);
        return putPromise.then(() => {
            expect(mediaFormatStore.saving).toEqual(false);
            expect(mediaFormatStore.getFormatOptions('x300')).toEqual(cropData);
            expect(mediaFormatStore.getFormatOptions('300x')).toEqual(mediaFormats['300x']);
        });
    });
});
