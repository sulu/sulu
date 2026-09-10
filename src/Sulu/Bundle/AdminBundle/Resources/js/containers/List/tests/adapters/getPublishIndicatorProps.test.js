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
        .toEqual({draft: true, published: false, review: false});
});

test('Return the dots of the workflow place for a row that has one', () => {
    expect(getPublishIndicatorProps({workflowPlace: 'review_draft'}))
        .toEqual({draft: false, published: true, review: true});
});
