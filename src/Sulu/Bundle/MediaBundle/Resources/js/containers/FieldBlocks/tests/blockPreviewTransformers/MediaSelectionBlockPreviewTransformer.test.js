// @flow
import MediaSelectionBlockPreviewTransformer
    from '../../blockPreviewTransformers/MediaSelectionBlockPreviewTransformer';

const MEDIA_URL = '/admin/media/redirect/media/:id';

test('Render a single image if an id is given', () => {
    const mediaSelectionBlockPreviewTransformer = new MediaSelectionBlockPreviewTransformer(MEDIA_URL);
    expect(mediaSelectionBlockPreviewTransformer.transform({ids: [3, 7]})).toMatchSnapshot();
});

test('Render nothing if no id is given', () => {
    const mediaSelectionBlockPreviewTransformer = new MediaSelectionBlockPreviewTransformer(MEDIA_URL);
    expect(mediaSelectionBlockPreviewTransformer.transform({ids: []})).toMatchSnapshot();
});

test('Render nothing if a wrong type of value is given', () => {
    const mediaSelectionBlockPreviewTransformer = new MediaSelectionBlockPreviewTransformer(MEDIA_URL);
    expect(mediaSelectionBlockPreviewTransformer.transform('')).toMatchSnapshot();
});
