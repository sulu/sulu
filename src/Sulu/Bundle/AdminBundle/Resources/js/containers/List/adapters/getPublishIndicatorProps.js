// @flow
import getWorkflowDots from '../../../utils/getWorkflowDots';
import type {WorkflowDots} from '../../../utils/getWorkflowDots';

/**
 * Returns `null` for a row that needs no indicator, a fully published item would only add noise.
 */
export default function getPublishIndicatorProps(item: Object): ?WorkflowDots {
    const {published, publishedState, workflowPlace} = item;

    if (publishedState === undefined && published === undefined && workflowPlace === undefined) {
        return null;
    }

    const draft = !publishedState;
    const isPublished = !!published;

    const hasInterestingState = workflowPlace !== undefined
        ? workflowPlace !== 'published'
        : (draft || !isPublished);

    return hasInterestingState ? getWorkflowDots(workflowPlace, draft, isPublished) : null;
}
