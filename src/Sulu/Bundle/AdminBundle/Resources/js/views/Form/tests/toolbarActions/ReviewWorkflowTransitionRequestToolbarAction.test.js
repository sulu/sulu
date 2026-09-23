// @flow
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import ResourceRequester from '../../../../services/ResourceRequester';
import ReviewWorkflowTransitionRequestToolbarAction
    from '../../toolbarActions/ReviewWorkflowTransitionRequestToolbarAction';

const mockUserStoreUser = jest.fn();

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../services/ResourceRequester', () => ({
    post: jest.fn(),
}));

jest.mock('../../../../stores/userStore', () => ({
    get user() {
        return mockUserStoreUser();
    },
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function() {
    this.data = {};
    this.reload = jest.fn();
}));

jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => (
    class {
        resourceStore;
        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get data() {
            return this.resourceStore.data;
        }
    }
));

jest.mock('../../../../services/Router', () => jest.fn());

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
    this.trigger = jest.fn();
}));

const request = {
    approvalProgress: {approved: 0, rejected: 0, required: 2},
    createdBy: {fullName: 'Adam Ministrator', id: 5},
    id: 'request-1',
    locale: 'en',
    permissions: {cancel: true, publish: true, retry: true, review: true},
    requestedAt: '2026-01-01T08:00:00+00:00',
    resourceId: '3',
    resourceKey: 'pages',
    approvals: [],
    checks: [],
    status: 'pending',
};

function createReviewToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new ReviewWorkflowTransitionRequestToolbarAction(
        resourceFormStore,
        form,
        router,
        [],
        options,
        resourceStore
    );
}

beforeEach(() => {
    ResourceRequester.post.mockClear();
    ResourceRequester.post.mockReturnValue(Promise.resolve({}));
    mockUserStoreUser.mockReturnValue({id: 9});
});

test('Do not let the user who created the request decide on it', () => {
    mockUserStoreUser.mockReturnValue({id: 5});

    const toolbarAction = createReviewToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.activeWorkflowTransitionRequest = request;

    expect(toolbarAction.canAct).toBe(false);
});

test('Let a user who did not create the request decide on it', () => {
    const toolbarAction = createReviewToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.activeWorkflowTransitionRequest = request;

    expect(toolbarAction.canAct).toBe(true);
});

test('Approve the request and reload the resource afterwards', async() => {
    const toolbarAction = createReviewToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.activeWorkflowTransitionRequest = request;

    await toolbarAction.handleApprove(undefined);

    expect(ResourceRequester.post).toHaveBeenCalledWith(
        'workflow_transition_requests',
        {comment: null},
        {id: 'request-1', action: 'approve'}
    );
    expect(toolbarAction.resourceFormStore.resourceStore.reload).toHaveBeenCalled();
});

test('Reject the request with the given comment and reload the resource afterwards', async() => {
    const toolbarAction = createReviewToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.activeWorkflowTransitionRequest = request;

    await toolbarAction.handleReject('Please fix the title');

    expect(ResourceRequester.post).toHaveBeenCalledWith(
        'workflow_transition_requests',
        {comment: 'Please fix the title'},
        {id: 'request-1', action: 'reject'}
    );
    expect(toolbarAction.resourceFormStore.resourceStore.reload).toHaveBeenCalled();
});

test('Publish without sending the form, which the workflow would refuse to write', () => {
    const toolbarAction = createReviewToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.activeWorkflowTransitionRequest = request;

    toolbarAction.handlePublish();

    expect(toolbarAction.form.trigger).toHaveBeenCalledWith('publish');
});

test('Report no decision of the current user while they have not decided', () => {
    const toolbarAction = createReviewToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.activeWorkflowTransitionRequest = request;

    expect(toolbarAction.userDecision).toBeUndefined();
});

test('An editor without the review permission may retry but not decide', () => {
    const toolbarAction = createReviewToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.activeWorkflowTransitionRequest = {
        ...request,
        permissions: {cancel: true, publish: false, retry: true, review: false},
    };

    expect(toolbarAction.canRetry).toBe(true);
    expect(toolbarAction.canAct).toBe(false);
    expect(toolbarAction.canPublish).toBe(false);
});

test('A reviewer who is not the author may decide', () => {
    const toolbarAction = createReviewToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.activeWorkflowTransitionRequest = request;

    expect(toolbarAction.canAct).toBe(true);
    expect(toolbarAction.canRetry).toBe(true);
    expect(toolbarAction.canPublish).toBe(true);
});

/**
 * Articles and snippets carry no permissions of their own, so a request that answers nothing must
 * not be read as permission granted.
 */
test('Grant nothing while the request carries no permissions', () => {
    const toolbarAction = createReviewToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.activeWorkflowTransitionRequest = {
        ...request,
        permissions: undefined,
    };

    expect(toolbarAction.canRetry).toBe(false);
    expect(toolbarAction.canAct).toBe(false);
    expect(toolbarAction.canPublish).toBe(false);
});

test('Report the decision the current user already made on the request', () => {
    const toolbarAction = createReviewToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.activeWorkflowTransitionRequest = {
        ...request,
        approvals: [
            {
                comment: null,
                decidedAt: '2026-01-01T09:00:00+00:00',
                id: 'approval-1',
                reviewer: {fullName: 'Other Reviewer', id: 7},
                status: 'rejected',
            },
            {
                comment: null,
                decidedAt: '2026-01-01T10:00:00+00:00',
                id: 'approval-2',
                reviewer: {fullName: 'Current User', id: 9},
                status: 'approved',
            },
        ],
    };

    expect(toolbarAction.userDecision).toBe('approved');
});

test('Retry a validator and reload the resource afterwards', async() => {
    const toolbarAction = createReviewToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.data.activeWorkflowTransitionRequest = request;

    await toolbarAction.handleRetry('broken_links');

    expect(ResourceRequester.post).toHaveBeenCalledWith(
        'workflow_transition_requests',
        {},
        {id: 'request-1', action: 'retry', validator: 'broken_links'}
    );
    expect(toolbarAction.resourceFormStore.resourceStore.reload).toHaveBeenCalled();
});

test('Return no item config and no node without an active request', () => {
    const toolbarAction = createReviewToolbarAction();

    expect(toolbarAction.getToolbarItemConfig()).toBeUndefined();
    expect(toolbarAction.getNode(0)).toBeNull();
});

test('Return item config with disabled button if passed disabled_condition is met', () => {
    const toolbarAction = createReviewToolbarAction({disabled_condition: '!_permissions.review'});
    toolbarAction.resourceFormStore.resourceStore.data.activeWorkflowTransitionRequest = request;
    toolbarAction.resourceFormStore.resourceStore.data._permissions = {review: false};

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        label: 'sulu_content.workflow_transition_request.review_action',
        disabled: true,
        type: 'button',
    }));
});
