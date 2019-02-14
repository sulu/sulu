// @flow
import StringBlockPreviewTransformer from '../../blockPreviewTransformers/StringBlockPreviewTransformer';

test('Return JSX for simple string', () => {
    const stringBlockPreviewTransformer = new StringBlockPreviewTransformer();
    expect(stringBlockPreviewTransformer.transform('Test')).toMatchSnapshot();
});

test('Return null for everything expect a string', () => {
    const stringBlockPreviewTransformer = new StringBlockPreviewTransformer();
    expect(stringBlockPreviewTransformer.transform({})).toMatchSnapshot();
});
