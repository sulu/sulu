// @flow
import SmartContentBlockPreviewTransformer from '../../blockPreviewTransformers/SmartContentBlockPreviewTransformer';
import {translate} from '../../../../utils/Translator';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Return JSX for configuration with a limit of 5', () => {
    const smartContentBlockPreviewTransformer = new SmartContentBlockPreviewTransformer();
    expect(smartContentBlockPreviewTransformer.transform({limitResult: 5})).toMatchSnapshot();
    expect(translate).toBeCalledWith('sulu_admin.smart_content_block_preview', {limit: 5});
});

test('Return null for everything expect a string', () => {
    const smartContentBlockPreviewTransformer = new SmartContentBlockPreviewTransformer();
    expect(smartContentBlockPreviewTransformer.transform({})).toMatchSnapshot();
    expect(translate).toBeCalledWith('sulu_admin.smart_content_block_preview', {limit: 'undefined'});
});
