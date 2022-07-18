// @flow
import SingleMediaSelectionBlockPreviewTransformer
    from '../../blockPreviewTransformers/SingleMediaSelectionBlockPreviewTransformer';

const MEDIA_URL = '/admin/media/redirect/media/:id';

test('Render a single image if an id is given', () => {
    const singleMediaSelectionBlockPreviewTransformer = new SingleMediaSelectionBlockPreviewTransformer(MEDIA_URL);
    expect(singleMediaSelectionBlockPreviewTransformer.transform({id: 5})).toMatchSnapshot();
});

test('Render nothing if no id is given', () => {
    const singleMediaSelectionBlockPreviewTransformer = new SingleMediaSelectionBlockPreviewTransformer(MEDIA_URL);
    expect(singleMediaSelectionBlockPreviewTransformer.transform({})).toMatchSnapshot();
});

test('Render nothing if a wrong type of value is given', () => {
    const singleMediaSelectionBlockPreviewTransformer = new SingleMediaSelectionBlockPreviewTransformer(MEDIA_URL);
    expect(singleMediaSelectionBlockPreviewTransformer.transform('')).toMatchSnapshot();
});
