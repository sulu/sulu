// @flow
export type WorkflowDots = {|
    draft: boolean,
    published: boolean,
    review: boolean,
|};

/**
 * Content that carries no workflow place falls back to the plain draft and published flags.
 */
export default function getWorkflowDots(workflowPlace: ?string, draft: boolean, published: boolean): WorkflowDots {
    switch (workflowPlace) {
        case 'published':
            return {draft: false, published: true, review: false};
        case 'unpublished':
            return {draft: true, published: false, review: false};
        case 'review':
            return {draft: false, published: false, review: true};
        case 'draft':
            return {draft: true, published: true, review: false};
        case 'review_draft':
            return {draft: false, published: true, review: true};
        default:
            return {draft, published, review: false};
    }
}
