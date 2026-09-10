// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import WorkflowTransitionRequestTimeline from '../components/WorkflowTransitionRequestTimeline';
import type {WorkflowTransitionRequestData} from '../components/types';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key, params) => (params ? key + ':' + JSON.stringify(params) : key)),
}));

function createRequest(overrides?: $Shape<WorkflowTransitionRequestData> = {}): WorkflowTransitionRequestData {
    return {
        approvalProgress: {approved: 2, rejected: 1, required: 2},
        createdBy: {fullName: 'Adam Ministrator', id: 1},
        id: 'request-1',
        locale: 'en',
        requestedAt: '2026-01-01T08:00:00+00:00',
        resourceId: '5',
        resourceKey: 'pages',
        checks: [],
        approvals: [
            {
                decidedAt: '2026-01-01T10:00:00+00:00',
                id: 'approval-first',
                messages: [],
                reviewer: {fullName: 'First Approver', id: 2},
                status: 'approved',
            },
            {
                decidedAt: '2026-01-01T11:00:00+00:00',
                id: 'approval-second',
                reviewer: {fullName: 'Second Approver', id: 3},
                status: 'approved',
            },
            {
                messages: [{key: null, parameters: {}, text: 'Not good'}],
                decidedAt: '2026-01-01T12:00:00+00:00',
                id: 'approval-third',
                reviewer: {fullName: 'Rejector', id: 4},
                status: 'rejected',
            },
        ],
        status: 'pending',
        ...overrides,
    };
}

test('shows the running approval count at each event instead of the final aggregate', async() => {
    const user = userEvent.setup();

    render(
        <WorkflowTransitionRequestTimeline request={createRequest()}>
            Status
        </WorkflowTransitionRequestTimeline>
    );

    await user.hover(screen.getByText('Status'));

    const labels = Array.from(document.querySelectorAll('.label')).map((label) => label.textContent);

    // newest first: rejection, second approval (running 2/2), first approval (running 1/2), request creation
    expect(labels).toEqual([
        'sulu_content.workflow_transition_request.timeline_rejected',
        'sulu_content.workflow_transition_request.timeline_approved:{"current":2,"total":2}',
        'sulu_content.workflow_transition_request.timeline_approved:{"current":1,"total":2}',
        'sulu_content.workflow_transition_request.timeline_requested',
    ]);
});

test('labels a passed check instead of numbering it, because a check is not an approval', async() => {
    const user = userEvent.setup();
    const request = createRequest({
        approvalProgress: {approved: 1, rejected: 0, required: 2},
        approvals: [
            {
                decidedAt: '2026-01-01T10:00:00+00:00',
                id: 'approval-first',
                messages: [],
                reviewer: {fullName: 'First Approver', id: 2},
                status: 'approved',
            },
        ],
        checks: [
            {
                required: false,
                messages: [],
                decidedAt: '2026-01-01T09:00:00+00:00',
                id: 'check-1',
                status: 'approved',
                validatorKey: 'broken_links',
            },
        ],
    });

    render(
        <WorkflowTransitionRequestTimeline request={request}>
            Status
        </WorkflowTransitionRequestTimeline>
    );

    await user.hover(screen.getByText('Status'));

    expect(Array.from(document.querySelectorAll('.label')).map((label) => label.textContent)).toEqual([
        'sulu_content.workflow_transition_request.timeline_approved:{"current":1,"total":2}',
        'sulu_content.workflow_transition_request.timeline_check_passed',
        'sulu_content.workflow_transition_request.timeline_requested',
    ]);
});

test('names the deciders and leaves out the reviewers who have not decided yet', async() => {
    const user = userEvent.setup();
    const request = createRequest({
        approvalProgress: {approved: 1, rejected: 0, required: 3},
        approvals: [],
        checks: [
            {
                required: false,
                messages: [],
                decidedAt: '2026-01-01T09:00:00+00:00',
                id: 'check-1',
                status: 'approved',
                validatorKey: 'broken_links',
            },
            {
                required: false,
                messages: [],
                decidedAt: null,
                id: 'check-2',
                status: 'pending',
                validatorKey: 'unpublished_references',
            },
        ],
    });

    render(
        <WorkflowTransitionRequestTimeline request={request}>
            Status
        </WorkflowTransitionRequestTimeline>
    );

    await user.hover(screen.getByText('Status'));

    expect(Array.from(document.querySelectorAll('.name')).map((name) => name.textContent)).toEqual([
        'broken_links',
        'Adam Ministrator',
    ]);
});
