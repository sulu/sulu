// @flow
import getWorkflowDots from '../getWorkflowDots';

test.each([
    ['published', {draft: false, published: true, review: false}],
    ['unpublished', {draft: true, published: false, review: false}],
    ['review', {draft: false, published: false, review: true}],
    ['draft', {draft: true, published: true, review: false}],
    ['review_draft', {draft: false, published: true, review: true}],
])('Return the dots for the workflow place "%s"', (workflowPlace, expected) => {
    expect(getWorkflowDots(workflowPlace, false, false)).toEqual(expected);
});

test('Fall back to the draft and published flags without a workflow place', () => {
    expect(getWorkflowDots(undefined, true, false)).toEqual({draft: true, published: false, review: false});
    expect(getWorkflowDots(null, false, true)).toEqual({draft: false, published: true, review: false});
});
