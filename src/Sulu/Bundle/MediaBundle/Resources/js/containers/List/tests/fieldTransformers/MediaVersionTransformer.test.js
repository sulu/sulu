// @flow
import MediaVersionTransformer from '../../fieldTransformers/MediaVersionTransformer';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key === 'sulu_media.version' ? 'Version' : key),
}));

const mediaVersionTransformer = new MediaVersionTransformer();

test.each([
    [3, 'Version 3'],
    ['3', 'Version 3'],
    [undefined, null],
    [null, null],
])('Transform %s', (value, expected) => {
    expect(mediaVersionTransformer.transform(value)).toEqual(expected);
});
