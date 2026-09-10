// @flow
import {hasRequestWorkflow, setRequestWorkflowTemplates} from '../requestWorkflowConfig';

test('Report a template the server listed for that resource', () => {
    setRequestWorkflowTemplates({pages: ['default', 'review'], articles: []});

    expect(hasRequestWorkflow('pages', 'review')).toBe(true);
    expect(hasRequestWorkflow('pages', 'homepage')).toBe(false);
    expect(hasRequestWorkflow('articles', 'default')).toBe(false);
});

test('Report nothing for a resource the server did not list', () => {
    setRequestWorkflowTemplates({pages: ['default']});

    expect(hasRequestWorkflow('snippets', 'default')).toBe(false);
});

test('Report nothing without a resource key or template', () => {
    setRequestWorkflowTemplates({pages: ['default']});

    expect(hasRequestWorkflow(undefined, 'default')).toBe(false);
    expect(hasRequestWorkflow('pages', undefined)).toBe(false);
});

test('Report nothing when no workflow is configured at all', () => {
    setRequestWorkflowTemplates(undefined);

    expect(hasRequestWorkflow('pages', 'default')).toBe(false);
});
