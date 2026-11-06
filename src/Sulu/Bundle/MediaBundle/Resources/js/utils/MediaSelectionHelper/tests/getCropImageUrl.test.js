// @flow
import getCropImageUrl from '../getCropImageUrl';

const media = {
    adminUrl: '/admin/image.jpg',
    id: 1,
    locale: 'en',
    mimeType: 'image/jpeg',
    thumbnails: {},
    title: 'Image',
    url: '/image.jpg',
};

test('Return the admin url of an image if formats are given', () => {
    expect(getCropImageUrl(media, ['400x400'])).toBe('/admin/image.jpg');
});

test('Return the url if the image has no admin url', () => {
    expect(getCropImageUrl({...media, adminUrl: undefined}, ['400x400'])).toBe('/image.jpg');
});

test('Return nothing if no formats are given', () => {
    expect(getCropImageUrl(media, [])).toBeUndefined();
});

test('Return nothing if the media is not an image', () => {
    expect(getCropImageUrl({...media, mimeType: 'application/pdf'}, ['400x400'])).toBeUndefined();
});
