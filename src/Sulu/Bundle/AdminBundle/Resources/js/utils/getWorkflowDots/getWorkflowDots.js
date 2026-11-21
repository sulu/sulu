// @flow
export type WorkflowDots = {|
    draft: boolean,
    published: boolean,
|};

/**
 * Content that carries no workflow place falls back to the plain draft and published flags.
 * There is no review dot yet, so content in review shows the dots of the place it would have without the review.
 */
export default function getWorkflowDots(workflowPlace: ?string, draft: boolean, published: boolean): WorkflowDots {
    switch (workflowPlace) {
        case 'published':
            return {draft: false, published: true};
        case 'unpublished':
        case 'review':
            return {draft: true, published: false};
        case 'draft':
        case 'review_draft':
            return {draft: true, published: true};
        default:
            return {draft, published};
    }
}
