// @flow
import StripHtmlBlockPreviewTransformer from '../../blockPreviewTransformers/StripHtmlBlockPreviewTransformer';

test('Return JSX for simple string', () => {
    const stripHtmlBlockPreviewTransformer = new StripHtmlBlockPreviewTransformer();
    expect(stripHtmlBlockPreviewTransformer.transform('<strong>Test</strong>')).toMatchSnapshot();
});

test('Return JSX for simple string', () => {
    const stripHtmlBlockPreviewTransformer = new StripHtmlBlockPreviewTransformer();
    expect(stripHtmlBlockPreviewTransformer.transform('<strong>' + 'c'.repeat(1000) + '</strong>')).toMatchSnapshot();
});

test('Return null for everything expect a string', () => {
    const stripHtmlBlockPreviewTransformer = new StripHtmlBlockPreviewTransformer();
    expect(stripHtmlBlockPreviewTransformer.transform({})).toMatchSnapshot();
});
