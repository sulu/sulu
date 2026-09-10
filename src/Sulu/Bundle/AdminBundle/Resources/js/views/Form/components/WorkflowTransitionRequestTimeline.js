// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import ArrowMenu from '../../../components/ArrowMenu';
import {translate} from '../../../utils/Translator';
import {approverName, validatorName} from './reviewers';
import workflowTransitionRequestTimelineStyles from './workflowTransitionRequestTimeline.scss';
import type {Node} from 'react';
import type {WorkflowTransitionRequestApproval, WorkflowTransitionRequestData} from './types';

type Props = {|
    children: Node,
    request: WorkflowTransitionRequestData,
|};

type TimelineEntry = {|
    id: string,
    label: string,
    name: string,
    timestamp: string,
|};

const DATE_FORMATTER = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
});

function decisionTime(entry: {+decidedAt: ?string}): number {
    return new Date(entry.decidedAt ?? 0).getTime();
}

// Numbers the approvals 1..N in decision order, so each entry shows the count as of that moment.
// Only people are numbered: a check does not count towards the gate, so it carries no "n of m".
function buildApprovalRunningCounts(approvals: Array<WorkflowTransitionRequestApproval>): Map<string, number> {
    const runningCounts = new Map();

    approvals
        .filter((approval) => approval.status === 'approved')
        .slice()
        .sort((left, right) => decisionTime(left) - decisionTime(right))
        .forEach((approval, index) => {
            runningCounts.set(approval.id, index + 1);
        });

    return runningCounts;
}

function buildEntries(request: WorkflowTransitionRequestData): Array<TimelineEntry> {
    const {required: requiredCount} = request.approvalProgress;
    const approvalRunningCounts = buildApprovalRunningCounts(request.approvals);

    const decided = [
        ...request.approvals.map((approval) => ({
            id: approval.id,
            decidedAt: approval.decidedAt,
            label: approval.status !== 'approved'
                ? translate('sulu_content.workflow_transition_request.timeline_rejected')
                : translate(
                    'sulu_content.workflow_transition_request.timeline_approved',
                    {current: approvalRunningCounts.get(approval.id) || 0, total: requiredCount}
                ),
            name: approverName(approval),
        })),
        ...request.checks
            .filter((check) => check.status !== 'pending')
            .map((check) => ({
                id: check.id,
                decidedAt: check.decidedAt,
                label: check.status === 'approved'
                    ? translate('sulu_content.workflow_transition_request.timeline_check_passed')
                    : translate('sulu_content.workflow_transition_request.timeline_rejected'),
                name: validatorName(check.validatorKey),
            })),
    ];

    const entries: Array<TimelineEntry> = decided
        .sort((left, right) => decisionTime(right) - decisionTime(left))
        .map((entry) => ({
            id: entry.id,
            label: entry.label,
            name: entry.name,
            timestamp: DATE_FORMATTER.format(decisionTime(entry)),
        }));

    entries.push({
        id: 'requested',
        label: translate('sulu_content.workflow_transition_request.timeline_requested'),
        name: request.createdBy ? request.createdBy.fullName : translate('sulu_admin.unknown_user'),
        timestamp: DATE_FORMATTER.format(new Date(request.requestedAt)),
    });

    return entries;
}

@observer
class WorkflowTransitionRequestTimeline extends React.Component<Props> {
    @observable open: boolean = false;

    @action handleEnter = () => {
        this.open = true;
    };

    @action handleLeave = () => {
        this.open = false;
    };

    render() {
        const {children, request} = this.props;
        const entries = buildEntries(request);

        const anchor = (
            <span
                className={workflowTransitionRequestTimelineStyles.anchor}
                onBlur={this.handleLeave}
                onFocus={this.handleEnter}
                onMouseEnter={this.handleEnter}
                onMouseLeave={this.handleLeave}
                role="button"
                tabIndex={0}
            >
                {children}
            </span>
        );

        return (
            <ArrowMenu
                anchorElement={anchor}
                backdrop={false}
                horizontalAnchorMode="center"
                open={this.open}
                skin="dark"
            >
                <div className={workflowTransitionRequestTimelineStyles.content}>
                    {entries.map((entry) => (
                        <div key={entry.id}>
                            <div className={workflowTransitionRequestTimelineStyles.label}>
                                {entry.label}
                            </div>
                            <div className={workflowTransitionRequestTimelineStyles.meta}>
                                {entry.timestamp}
                                {' ・ '}
                                <span className={workflowTransitionRequestTimelineStyles.name}>{entry.name}</span>
                            </div>
                        </div>
                    ))}
                </div>
            </ArrowMenu>
        );
    }
}

export default WorkflowTransitionRequestTimeline;
