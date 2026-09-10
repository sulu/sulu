// @flow

/** Template keys covered by a review workflow, per resource key. */
export type RequestWorkflowTemplates = {[resourceKey: string]: Array<string>};

let requestWorkflowTemplates: RequestWorkflowTemplates = {};

export function setRequestWorkflowTemplates(templates: ?RequestWorkflowTemplates) {
    requestWorkflowTemplates = templates || {};
}

/**
 * Saved content carries `workflowTransitionRequestEnabled`, so this is only for the create form,
 * which has nothing saved to ask and has to go by the template the author picked.
 */
export function hasRequestWorkflow(resourceKey: ?string, templateKey: mixed): boolean {
    if (!resourceKey || typeof templateKey !== 'string') {
        return false;
    }

    return (requestWorkflowTemplates[resourceKey] || []).includes(templateKey);
}
