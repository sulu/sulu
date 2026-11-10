// @flow
import getWorkflowDots from '../getWorkflowDots';

test.each([
    ['published', {draft: false, published: true}],
    ['unpublished', {draft: true, published: false}],
    ['review', {draft: true, published: false}],
    ['draft', {draft: true, published: true}],
    ['review_draft', {draft: true, published: true}],
])('Return the dots for the workflow place "%s"', (workflowPlace, expected) => {
    expect(getWorkflowDots(workflowPlace, false, false)).toEqual(expected);
});

test('Fall back to the draft and published flags without a workflow place', () => {
    expect(getWorkflowDots(undefined, true, false)).toEqual({draft: true, published: false});
    expect(getWorkflowDots(null, false, true)).toEqual({draft: false, published: true});
});
