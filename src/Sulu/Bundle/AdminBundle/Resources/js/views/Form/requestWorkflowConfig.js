// @flow

/** Template keys covered by a review workflow, per resource key. */
export type RequestWorkflowTemplates = {[resourceKey: string]: Array<string>};

let requestWorkflowTemplates: RequestWorkflowTemplates = {};

export function setRequestWorkflowTemplates(templates: ?RequestWorkflowTemplates) {
    requestWorkflowTemplates = templates || {};
}

/** Only for the create form: saved content answers through `workflowTransitionRequestEnabled`. */
export function hasRequestWorkflow(resourceKey: ?string, templateKey: mixed): boolean {
    if (!resourceKey || typeof templateKey !== 'string') {
        return false;
    }

    return (requestWorkflowTemplates[resourceKey] || []).includes(templateKey);
}
