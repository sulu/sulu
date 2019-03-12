// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import MediaFormatStore from '../MediaFormatStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
    patch: jest.fn().mockReturnValue({
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

test('Update media formats', () => {
    const mediaFormats = {
        '300x': {
            cropX: 300,
            cropY: 100,
        },
        'x300': {
            cropX: 100,
            cropY: 100,
        },
        '300x300': {
            cropX: 300,
            cropY: 300,
        },
    };
    const listPromise = Promise.resolve(mediaFormats);
    ResourceRequester.getList.mockReturnValue(listPromise);
    const mediaFormatStore = new MediaFormatStore(4, 'de');

    return listPromise.then(() => {
        const cropData = {
            '300x': {cropX: 60, cropY: 120, cropHeight: 100, cropWidth: 200},
            'x300': {cropX: 30, cropY: 140, cropHeight: 120, cropWidth: 220},
        };
        const patchPromise = Promise.resolve(cropData);
        ResourceRequester.patch.mockReturnValue(patchPromise);
        mediaFormatStore.updateFormatOptions(cropData);

        expect(mediaFormatStore.saving).toEqual(true);
        return patchPromise.then(() => {
            expect(mediaFormatStore.saving).toEqual(false);
            expect(mediaFormatStore.getFormatOptions('x300')).toEqual(cropData['x300']);
            expect(mediaFormatStore.getFormatOptions('300x')).toEqual(cropData['300x']);
            expect(mediaFormatStore.getFormatOptions('300x300')).toEqual(mediaFormats['300x300']);
        });
    });
});
