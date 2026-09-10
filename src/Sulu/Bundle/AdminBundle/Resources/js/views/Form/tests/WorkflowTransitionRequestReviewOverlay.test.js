// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import WorkflowTransitionRequestReviewOverlay from '../components/WorkflowTransitionRequestReviewOverlay';
import type {WorkflowTransitionRequestData} from '../components/types';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key, params) => (params ? key + ':' + JSON.stringify(params) : key)),
}));

jest.mock('../../../stores/userStore', () => ({
    user: {id: 1},
}));

function createRequest(overrides?: $Shape<WorkflowTransitionRequestData> = {}): WorkflowTransitionRequestData {
    return {
        approvalProgress: {approved: 2, rejected: 2, required: 3},
        createdBy: {fullName: 'Adam Ministrator', id: 1},
        id: 'request-1',
        locale: 'en',
        requestedAt: '2026-06-25T14:30:00+00:00',
        resourceId: '5',
        resourceKey: 'pages',
        checks: [
            {
                required: false,
                messages: [],
                decidedAt: '2026-06-25T14:31:00+00:00',
                id: 'check-1',
                status: 'approved',
                validatorKey: 'unpublished_references',
            },
            {
                required: false,
                messages: [{key: null, parameters: {}, text: 'Two links are broken'}],
                decidedAt: '2026-06-25T14:32:00+00:00',
                id: 'check-2',
                status: 'rejected',
                validatorKey: 'broken_links',
            },
        ],
        approvals: [
            {
                decidedAt: '2026-06-25T15:00:00+00:00',
                id: 'approval-1',
                reviewer: {fullName: 'Alpha Bot', id: 2},
                status: 'approved',
            },
            {
                messages: [{key: null, parameters: {}, text: 'Please fix the title'}],
                decidedAt: '2026-06-25T15:05:00+00:00',
                id: 'approval-2',
                reviewer: {fullName: 'Anna Berger', id: 3},
                status: 'rejected',
            },
        ],
        status: 'pending',
        ...overrides,
    };
}

function renderOverlay(props?: Object = {}) {
    return render(
        <WorkflowTransitionRequestReviewOverlay
            canAct={props.canAct ?? true}
            canPublish={props.canPublish}
            canPublishWithoutReview={props.canPublishWithoutReview}
            canRetry={props.canRetry ?? true}
            mode={props.mode}
            onApprove={props.onApprove ?? jest.fn()}
            onClose={props.onClose ?? jest.fn()}
            onPublish={props.onPublish}
            onReject={props.onReject ?? jest.fn()}
            onRetry={props.onRetry}
            open={true}
            request={props.request ?? createRequest()}
            userDecision={props.userDecision}
        />
    );
}

test('splits the checks and the people into two cards, people last', () => {
    renderOverlay();

    // 2 cards, so 2 headers
    expect(document.querySelectorAll('.card')).toHaveLength(2);
    // 2 validators + 2 users + max(0, required 3 - approved 2) waiting slots
    expect(document.querySelectorAll('.row')).toHaveLength(5);

    expect(Array.from(document.querySelectorAll('.rowTitle')).map((title) => title.textContent)).toEqual([
        'unpublished_references',
        'broken_links',
        'Alpha Bot',
        'Anna Berger',
    ]);

    expect(document.querySelectorAll('.approved')).toHaveLength(2);
    expect(document.querySelectorAll('.rejected')).toHaveLength(2);
    // an outstanding approval waits on a person, so it is amber rather than the grey of a check
    expect(document.querySelectorAll('.waiting')).toHaveLength(1);
    expect(document.querySelectorAll('.pending')).toHaveLength(0);
});

test('gives a check its own wording instead of borrowing a person\'s', () => {
    renderOverlay();

    expect(screen.getByText('sulu_content.workflow_transition_request.pre_validator_passed')).toBeInTheDocument();
    expect(screen.getByText('sulu_content.workflow_transition_request.check_caption_failed')).toBeInTheDocument();
    expect(
        screen.getByText('sulu_content.workflow_transition_request.reviewer_caption_approved')
    ).toBeInTheDocument();
});

test('counts the passed checks separately from the approvals', () => {
    renderOverlay();

    expect(screen.getByText('sulu_content.workflow_transition_request.automated_checks')).toBeInTheDocument();
    expect(screen.getByText(
        'sulu_content.workflow_transition_request.n_of_m_checks_passed:{"passed":1,"total":2}'
    )).toBeInTheDocument();
});

test('omits the check card when the workflow configured no validators', () => {
    const request = createRequest({
        approvalProgress: {approved: 0, rejected: 0, required: 1},
        approvals: [],
        checks: [],
    });

    renderOverlay({request});

    expect(document.querySelectorAll('.card')).toHaveLength(1);
    expect(
        screen.queryByText('sulu_content.workflow_transition_request.automated_checks')
    ).not.toBeInTheDocument();
});

test('renders a pending check in grey and still expects every required approval from a person', () => {
    const request = createRequest({
        approvalProgress: {approved: 0, rejected: 0, required: 2},
        approvals: [],
        checks: [
            {
                required: false,
                messages: [],
                decidedAt: null,
                id: 'check-1',
                status: 'pending',
                validatorKey: 'broken_links',
            },
        ],
    });

    renderOverlay({request});

    // the pending check does not stand in for a person, so both approvals are still outstanding
    expect(document.querySelectorAll('.row')).toHaveLength(3);
    expect(document.querySelectorAll('.pending')).toHaveLength(1);
    expect(document.querySelectorAll('.waiting')).toHaveLength(2);
});

test('counts the approvals and the rejections', () => {
    renderOverlay();

    expect(screen.getByText(
        'sulu_content.workflow_transition_request.n_of_m_approved:{"approved":2,"required":3}'
    )).toBeInTheDocument();
    expect(screen.getByText(
        'sulu_content.workflow_transition_request.n_rejected:{"rejected":2}'
    )).toBeInTheDocument();
});

test('hides the rejection count while nothing was rejected', () => {
    renderOverlay({request: createRequest({approvalProgress: {approved: 2, rejected: 0, required: 3}})});

    expect(screen.queryByText(/request\.n_rejected/)).not.toBeInTheDocument();
});

test('shows every comment right away, clamped to its first lines', () => {
    renderOverlay();

    // no chevron any more: a comment nobody opens is a comment nobody reads
    expect(screen.getByText('Two links are broken')).toBeInTheDocument();
    expect(screen.getByText('Please fix the title')).toBeInTheDocument();
    expect(screen.queryByLabelText('su-angle-down')).not.toBeInTheDocument();

    const comments = document.querySelectorAll('.rowCommentText');
    expect(comments).toHaveLength(2);
    comments.forEach((comment) => expect(comment).toHaveClass('rowCommentClamped'));
});

test('enables both decisions for a reviewer who has not decided yet', () => {
    renderOverlay();

    expect(screen.getByRole('button', {name: 'sulu_content.reject'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'sulu_content.approve'})).toBeEnabled();
});

test('disables the approval and renames it after the reviewer approved, but keeps the rejection open', () => {
    renderOverlay({userDecision: 'approved'});

    expect(screen.getByRole('button', {
        name: 'sulu_content.workflow_transition_request.you_approved',
    })).toBeDisabled();
    expect(screen.getByRole('button', {name: 'sulu_content.reject'})).toBeEnabled();
});

test('disables the rejection after the reviewer rejected, but keeps the approval open', () => {
    renderOverlay({userDecision: 'rejected'});

    expect(screen.getByRole('button', {name: 'sulu_content.reject'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'sulu_content.approve'})).toBeEnabled();
});

test('disables both decisions once the request is closed', () => {
    renderOverlay({request: createRequest({status: 'published'})});

    expect(screen.getByRole('button', {name: 'sulu_content.reject'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'sulu_content.approve'})).toBeDisabled();
});

test('approves with a comment', async() => {
    const user = userEvent.setup();
    const onApprove = jest.fn().mockResolvedValue();
    const onClose = jest.fn();

    renderOverlay({onApprove, onClose});

    await user.click(screen.getByRole('button', {name: 'sulu_content.approve'}));

    expect(screen.getByText(
        'sulu_content.workflow_transition_request.approve_comment_label'
    )).toBeInTheDocument();

    await user.type(
        screen.getByPlaceholderText('sulu_content.workflow_transition_request.comment_placeholder'),
        'Looks good'
    );
    await user.click(screen.getByRole('button', {name: 'sulu_admin.send'}));

    expect(onApprove).toHaveBeenCalledWith('Looks good');
    // The reviewer stays in the overlay and reads the refreshed request instead of reopening it.
    expect(onClose).not.toHaveBeenCalled();
    expect(document.querySelectorAll('.row')).toHaveLength(5);
});

test('approves without a comment, because the comment is optional', async() => {
    const user = userEvent.setup();
    const onApprove = jest.fn().mockResolvedValue();

    renderOverlay({onApprove});

    await user.click(screen.getByRole('button', {name: 'sulu_content.approve'}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.send'}));

    expect(onApprove).toHaveBeenCalledWith(null);
});

test('only allows sending a rejection once a comment was written', async() => {
    const user = userEvent.setup();
    const onReject = jest.fn().mockResolvedValue();
    const onClose = jest.fn();

    renderOverlay({onClose, onReject});

    await user.click(screen.getByRole('button', {name: 'sulu_content.reject'}));

    expect(screen.getByText(
        'sulu_content.workflow_transition_request.why_did_you_reject'
    )).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.send'})).toBeDisabled();

    await user.type(
        screen.getByPlaceholderText('sulu_content.workflow_transition_request.comment_placeholder'),
        'Please fix this'
    );
    await user.click(screen.getByRole('button', {name: 'sulu_admin.send'}));

    expect(onReject).toHaveBeenCalledWith('Please fix this');
    expect(onClose).not.toHaveBeenCalled();
    expect(document.querySelectorAll('.row')).toHaveLength(5);
});

test('offers a retry on every unapproved validator row and keeps the overlay open afterwards', async() => {
    const user = userEvent.setup();
    const onClose = jest.fn();
    const onRetry = jest.fn().mockResolvedValue();

    renderOverlay({onClose, onRetry});

    const retryButtons = screen.getAllByRole('button', {
        name: 'sulu_content.workflow_transition_request.retry',
    });
    expect(retryButtons).toHaveLength(1);

    await user.click(retryButtons[0]);

    expect(onRetry).toHaveBeenCalledWith('broken_links');
    expect(onClose).not.toHaveBeenCalled();
});

test('offers no retry once the request is closed, because the verdict cannot be redone', () => {
    renderOverlay({onRetry: jest.fn(), request: createRequest({status: 'published'})});

    expect(screen.queryByRole('button', {
        name: 'sulu_content.workflow_transition_request.retry',
    })).not.toBeInTheDocument();
});

test('offers no retry without the edit permission', () => {
    renderOverlay({canRetry: false, onRetry: jest.fn()});

    expect(screen.queryByRole('button', {
        name: 'sulu_content.workflow_transition_request.retry',
    })).not.toBeInTheDocument();
});

test('offers the retry to the author of the request, who may not review it', () => {
    // re-running a failed check is how the content gets fixed, so it must not need a reviewer
    renderOverlay({canAct: false, canRetry: true, onRetry: jest.fn()});

    expect(screen.getAllByRole('button', {
        name: 'sulu_content.workflow_transition_request.retry',
    })).toHaveLength(1);
});

test('renders no decision buttons and a notice when the viewer cannot act on their own request', () => {
    renderOverlay({canAct: false});

    expect(screen.queryByRole('button', {name: 'sulu_content.reject'})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'sulu_content.approve'})).not.toBeInTheDocument();
    expect(screen.getByText('sulu_content.workflow_transition_request.self_review_not_allowed')).toBeInTheDocument();
});

test('tells a viewer who did not create the request that they may not review it', () => {
    renderOverlay({canAct: false, request: createRequest({createdBy: {fullName: 'Someone Else', id: 2}})});

    expect(screen.queryByRole('button', {name: 'sulu_content.reject'})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'sulu_content.approve'})).not.toBeInTheDocument();
    expect(
        screen.queryByText('sulu_content.workflow_transition_request.self_review_not_allowed')
    ).not.toBeInTheDocument();
    expect(screen.getByText('sulu_content.workflow_transition_request.review_not_permitted')).toBeInTheDocument();
});

test('view mode renders the reviewers without decision buttons and without the self-review notice', () => {
    renderOverlay({canAct: false, mode: 'view'});

    expect(document.querySelectorAll('.row')).toHaveLength(5);
    expect(screen.queryByRole('button', {name: 'sulu_content.reject'})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'sulu_content.approve'})).not.toBeInTheDocument();
    expect(
        screen.queryByText('sulu_content.workflow_transition_request.self_review_not_allowed')
    ).not.toBeInTheDocument();
});

test('offers publish inside the overlay once the request is approved', async() => {
    const user = userEvent.setup();
    const publishSpy = jest.fn();

    // an editor may carry out an approved request, so the button must not need canAct
    renderOverlay({
        canAct: false,
        canPublish: true,
        onPublish: publishSpy,
        request: createRequest({status: 'approved'}),
    });

    await user.click(screen.getByRole('button', {name: 'sulu_admin.publish'}));

    expect(publishSpy).toHaveBeenCalled();
});

test('offers no publish while the request is still pending', () => {
    renderOverlay({canPublish: true, onPublish: jest.fn()});

    expect(screen.queryByRole('button', {name: 'sulu_admin.publish'})).not.toBeInTheDocument();
});

test('offers no publish without the permission, even when approved', () => {
    renderOverlay({canPublish: false, onPublish: jest.fn(), request: createRequest({status: 'approved'})});

    expect(screen.queryByRole('button', {name: 'sulu_admin.publish'})).not.toBeInTheDocument();
});

test('offers publishing without review to a live holder', async() => {
    const user = userEvent.setup();
    const publishSpy = jest.fn();

    renderOverlay({canPublishWithoutReview: true, onPublish: publishSpy});

    await user.click(screen.getByRole('button', {
        name: 'sulu_content.workflow_transition_request.publish_without_review',
    }));

    expect(publishSpy).toHaveBeenCalled();
});

test('offers no publishing without review once the request is approved', () => {
    renderOverlay({
        canPublishWithoutReview: true,
        onPublish: jest.fn(),
        request: createRequest({status: 'approved'}),
    });

    expect(screen.queryByRole('button', {
        name: 'sulu_content.workflow_transition_request.publish_without_review',
    })).not.toBeInTheDocument();
});

test('shows an error snackbar when approving fails and clears it on a successful retry', async() => {
    const user = userEvent.setup();
    let approveCallCount = 0;
    const onApprove = jest.fn(() => {
        approveCallCount += 1;
        return approveCallCount === 1 ? Promise.reject(new Error('network error')) : Promise.resolve();
    });
    const onClose = jest.fn();

    renderOverlay({onApprove, onClose});

    await user.click(screen.getByRole('button', {name: 'sulu_content.approve'}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.send'}));

    expect(document.querySelector('.snackbar')?.textContent).toContain(
        'sulu_content.workflow_transition_request.action_failed'
    );
    expect(onClose).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.send'}));

    expect(onApprove).toHaveBeenCalledTimes(2);
    // the Snackbar keeps rendering its last message during its own fade-out transition, so the
    // visible flag - not the text - is what proves the error was cleared on the successful retry
    expect(document.querySelector('.snackbar.visible')).toBeNull();
});

test('prefers the message the server sent over the generic failure message', async() => {
    const user = userEvent.setup();
    const onApprove = jest.fn(() => Promise.reject({json: () => Promise.resolve({detail: 'You already decided'})}));

    renderOverlay({onApprove});

    await user.click(screen.getByRole('button', {name: 'sulu_content.approve'}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.send'}));

    expect(document.querySelector('.snackbar')?.textContent).toContain('You already decided');
});

test('renders a no-reviewers message when nothing is expected and nobody decided', () => {
    const request = createRequest({
        approvalProgress: {approved: 0, rejected: 0, required: 0},
        approvals: [],
        checks: [],
    });

    renderOverlay({request});

    expect(screen.getByText('sulu_content.workflow_transition_request.no_reviewers')).toBeInTheDocument();
});
