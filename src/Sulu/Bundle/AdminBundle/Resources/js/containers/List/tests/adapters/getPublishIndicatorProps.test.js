// @flow
import getPublishIndicatorProps from '../../adapters/getPublishIndicatorProps';

test('Return nothing for a row without any publish information', () => {
    expect(getPublishIndicatorProps({id: 1})).toEqual(null);
});

test('Return nothing for a fully published row', () => {
    expect(getPublishIndicatorProps({publishedState: true, published: '2026-01-01'})).toEqual(null);
    expect(getPublishIndicatorProps({workflowPlace: 'published'})).toEqual(null);
});

test('Return the draft dot for a row that was never published', () => {
    expect(getPublishIndicatorProps({publishedState: false, published: null}))
        .toEqual({draft: true, published: false});
});

test('Return the draft and published dots for a published row with a draft, without the published date', () => {
    expect(getPublishIndicatorProps({publishedState: false, workflowPlace: 'draft'}))
        .toEqual({draft: true, published: true});
});
