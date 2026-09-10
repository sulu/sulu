// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable, runInAction} from 'mobx';
import Overlay from '../../../components/Overlay';
import Button from '../../../components/Button';
import TextArea from '../../../components/TextArea';
import {translate} from '../../../utils/Translator';
import userStore from '../../../stores/userStore';
import {approverName, validatorName} from './reviewers';
import {renderMessages} from './decisionMessages';
import WorkflowCheckCard from './WorkflowCheckCard';
import workflowCheckCardStyles from './workflowCheckCard.scss';
import workflowTransitionRequestReviewOverlayStyles from './workflowTransitionRequestReviewOverlay.scss';
import type {ApprovalStatus, CheckStatus, WorkflowTransitionRequestData} from './types';
import type {CheckRow} from './WorkflowCheckCard';
import type {Node} from 'react';

type Step = 'list' | 'approve' | 'reject';

const APPROVAL_CAPTION: {[ApprovalStatus]: string} = {
    approved: 'sulu_content.workflow_transition_request.reviewer_caption_approved',
    rejected: 'sulu_content.workflow_transition_request.reviewer_caption_rejected',
};

// A check reports on the content, it does not approve it, so it does not borrow a person's wording.
const CHECK_CAPTION: {[CheckStatus]: string} = {
    approved: 'sulu_content.workflow_transition_request.pre_validator_passed',
    pending: 'sulu_content.workflow_transition_request.check_caption_waiting',
    rejected: 'sulu_content.workflow_transition_request.check_caption_failed',
};

const DATE_FORMATTER = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
});

function resolveErrorDetail(error: Object): Promise<?string> {
    if (typeof error?.json !== 'function') {
        return Promise.resolve(undefined);
    }

    return error.json().then((data) => data?.detail).catch(() => undefined);
}

function isClosed(request: WorkflowTransitionRequestData): boolean {
    return request.status === 'cancelled' || request.status === 'published';
}

function buildCheckRows(request: WorkflowTransitionRequestData, canRetry: boolean): Array<CheckRow> {
    return request.checks.map((check) => ({
        actionLabel: canRetry && check.status !== 'approved'
            ? translate('sulu_content.workflow_transition_request.retry')
            : null,
        actionValue: check.validatorKey,
        // A required check that has not passed is the reason the request is still pending, so it
        // says so instead of leaving the reader to guess between "2 of 2 approved" and the banner.
        caption: check.required && check.status !== 'approved'
            ? translate('sulu_content.workflow_transition_request.check_caption_required')
            : translate(CHECK_CAPTION[check.status]),
        comment: renderMessages(check.messages),
        id: check.id,
        status: check.status,
        title: validatorName(check.validatorKey),
    }));
}

function buildApprovalRows(request: WorkflowTransitionRequestData): Array<CheckRow> {
    const rows = request.approvals.map((approval) => ({
        actionLabel: null,
        actionValue: null,
        caption: translate(APPROVAL_CAPTION[approval.status]),
        comment: renderMessages(approval.messages),
        id: approval.id,
        status: approval.status,
        title: approverName(approval),
    }));

    // Only people count towards the gate, so every approval still outstanding gets its own row.
    const {approved, required} = request.approvalProgress;

    for (let index = 0; index < Math.max(0, required - approved); index++) {
        rows.push({
            actionLabel: null,
            actionValue: null,
            caption: translate('sulu_content.workflow_transition_request.reviewer_caption_waiting'),
            comment: null,
            id: 'waiting-' + index,
            status: 'waiting',
            title: null,
        });
    }

    return rows;
}

type Props = {|
    canAct: boolean,
    /** Carrying out an approved request: live, or edit because the approval delegates the right. */
    canPublish?: boolean,
    /** Publishing past an unfinished review, which takes the live permission. */
    canPublishWithoutReview?: boolean,
    /** Re-running a check takes the edit permission, not the reviewer's, so it is its own answer. */
    canRetry?: boolean,
    mode?: 'review' | 'view',
    onApprove?: (comment: ?string) => Promise<mixed>,
    onClose: () => void,
    onPublish?: () => void,
    onReject?: (comment: string) => Promise<mixed>,
    onRetry?: (validatorKey: string) => Promise<mixed>,
    open: boolean,
    request: WorkflowTransitionRequestData,
    userDecision?: ?ApprovalStatus,
|};

@observer
class WorkflowTransitionRequestReviewOverlay extends React.Component<Props> {
    @observable step: Step = 'list';
    @observable comment: ?string = undefined;
    @observable submitting: boolean = false;
    @observable error: string | typeof undefined = undefined;

    @action handleCommentChange = (value: ?string) => {
        this.comment = value;
    };

    @action handleSnackbarCloseClick = () => {
        this.error = undefined;
    };

    @action reset = () => {
        this.step = 'list';
        this.comment = undefined;
        this.submitting = false;
        this.error = undefined;
    };

    handleClose = () => {
        if (this.submitting) {
            return;
        }

        this.reset();
        this.props.onClose();
    };

    @action handleApproveClick = () => {
        this.step = 'approve';
    };

    @action handleRejectClick = () => {
        this.step = 'reject';
    };

    handleSendClick = () => {
        const comment = (this.comment || '').trim();

        void this.send(
            this.step === 'reject'
                ? this.props.onReject?.(comment)
                : this.props.onApprove?.(comment || null)
        );
    };

    handleRetryClick = (validatorKey: ?string) => {
        if (!validatorKey) {
            return;
        }

        void this.send(this.props.onRetry?.(validatorKey));
    };

    /** Stays open on success: the caller reloads the request, and the reviewer reads the new state here. */
    @action async send(decision: ?Promise<mixed>) {
        if (this.submitting) {
            return;
        }

        this.submitting = true;
        this.error = undefined;

        try {
            await decision;
            this.reset();
        } catch (error) {
            const detail = await resolveErrorDetail(error);
            runInAction(() => {
                this.submitting = false;
                this.error = detail || translate('sulu_content.workflow_transition_request.action_failed');
            });
        }
    }

    renderFooterButtons(buttons: Array<Node>) {
        return (
            <div className={workflowCheckCardStyles.footerActions}>
                {buttons}
            </div>
        );
    }

    /**
     * An editor who may carry out an approved request has no publish entry in the toolbar dropdown.
     */
    renderPublishButtons(): Array<Node> {
        const {canPublish, canPublishWithoutReview, onPublish, request} = this.props;

        if (isClosed(request)) {
            return [];
        }

        if (request.status === 'approved') {
            return canPublish && onPublish
                ? [
                    <Button
                        className={workflowCheckCardStyles.confirmButton}
                        key="publish"
                        onClick={onPublish}
                        skin="primary"
                    >
                        {translate('sulu_admin.publish')}
                    </Button>,
                ]
                : [];
        }

        return canPublishWithoutReview && onPublish
            ? [
                <Button
                    className={workflowTransitionRequestReviewOverlayStyles.rejectButton}
                    key="publish_without_review"
                    onClick={onPublish}
                    skin="secondary"
                >
                    {translate('sulu_content.workflow_transition_request.publish_without_review')}
                </Button>,
            ]
            : [];
    }

    renderFooter() {
        const {canAct, mode = 'review', request, userDecision} = this.props;

        if (mode === 'view') {
            return undefined;
        }

        const publishButtons = this.renderPublishButtons();

        if (!canAct) {
            // An editor who may publish an approved request still needs its button.
            return publishButtons.length > 0 ? this.renderFooterButtons(publishButtons) : undefined;
        }

        if (this.step !== 'list') {
            return this.renderFooterButtons([
                <Button
                    className={workflowCheckCardStyles.confirmButton}
                    disabled={this.step === 'reject' && !(this.comment || '').trim()}
                    key="send"
                    loading={this.submitting}
                    onClick={this.handleSendClick}
                    skin="primary"
                >
                    {translate('sulu_admin.send')}
                </Button>,
            ]);
        }

        const closed = isClosed(request);

        return this.renderFooterButtons([
            <Button
                className={workflowTransitionRequestReviewOverlayStyles.rejectButton}
                disabled={closed || userDecision === 'rejected'}
                key="reject"
                onClick={this.handleRejectClick}
                skin="secondary"
            >
                {translate('sulu_content.reject')}
            </Button>,
            <Button
                className={workflowCheckCardStyles.confirmButton}
                disabled={closed || userDecision === 'approved'}
                key="approve"
                onClick={this.handleApproveClick}
                skin="primary"
            >
                {userDecision === 'approved'
                    ? translate('sulu_content.workflow_transition_request.you_approved')
                    : translate('sulu_content.approve')}
            </Button>,
            ...publishButtons,
        ]);
    }

    renderComment() {
        const label = this.step === 'reject'
            ? translate('sulu_content.workflow_transition_request.why_did_you_reject')
            : translate('sulu_content.workflow_transition_request.approve_comment_label');

        return (
            <div className={workflowTransitionRequestReviewOverlayStyles.commentBody}>
                <div className={workflowTransitionRequestReviewOverlayStyles.commentLabel}>{label}</div>
                <div className={workflowTransitionRequestReviewOverlayStyles.commentField}>
                    <TextArea
                        onChange={this.handleCommentChange}
                        placeholder={translate('sulu_content.workflow_transition_request.comment_placeholder')}
                        value={this.comment}
                    />
                </div>
            </div>
        );
    }

    renderList() {
        const {canAct, canRetry = false, mode = 'review', onRetry, request} = this.props;
        const {approved, rejected, required} = request.approvalProgress;
        const checkRows = buildCheckRows(request, canRetry && !!onRetry && !isClosed(request));
        const approvalRows = buildApprovalRows(request);
        const passedChecks = checkRows.filter((row) => row.status === 'approved').length;
        const requestedByName = request.createdBy
            ? request.createdBy.fullName
            : translate('sulu_admin.unknown_user');

        return (
            <div className={workflowCheckCardStyles.body}>
                {checkRows.length > 0 && (
                    <WorkflowCheckCard
                        headerCount={translate(
                            'sulu_content.workflow_transition_request.n_of_m_checks_passed',
                            {passed: passedChecks, total: checkRows.length}
                        )}
                        headerText={translate('sulu_content.workflow_transition_request.automated_checks')}
                        onAction={this.handleRetryClick}
                        rows={checkRows}
                    />
                )}
                <WorkflowCheckCard
                    emptyText={translate('sulu_content.workflow_transition_request.no_reviewers')}
                    headerCount={
                        <React.Fragment>
                            {translate(
                                'sulu_content.workflow_transition_request.n_of_m_approved',
                                {approved, required}
                            )}
                            {rejected > 0 && (
                                <span className={workflowCheckCardStyles.headerRejected}>
                                    {translate('sulu_content.workflow_transition_request.n_rejected', {rejected})}
                                </span>
                            )}
                        </React.Fragment>
                    }
                    headerDetail={DATE_FORMATTER.format(new Date(request.requestedAt))}
                    headerText={
                        <React.Fragment>
                            <strong>{requestedByName}</strong>
                            {' '}
                            {translate('sulu_content.workflow_transition_request.requested_a_review')}
                        </React.Fragment>
                    }
                    rows={approvalRows}
                />
                {mode === 'review' && !canAct && (
                    <p className={workflowTransitionRequestReviewOverlayStyles.notice}>
                        {translate(String(request.createdBy?.id) === String(userStore.user?.id)
                            ? 'sulu_content.workflow_transition_request.self_review_not_allowed'
                            : 'sulu_content.workflow_transition_request.review_not_permitted')}
                    </p>
                )}
            </div>
        );
    }

    render() {
        const {mode = 'review', open} = this.props;

        let title = translate('sulu_content.workflow_transition_request.review_action');
        if (this.step === 'reject') {
            title = translate('sulu_content.reject');
        } else if (this.step === 'approve') {
            title = translate('sulu_content.approve');
        }

        return (
            <Overlay
                footer={this.renderFooter()}
                onClose={this.handleClose}
                onSnackbarCloseClick={this.error ? this.handleSnackbarCloseClick : undefined}
                open={open}
                snackbarMessage={this.error}
                snackbarType="error"
                title={title}
            >
                {mode === 'review' && this.step !== 'list' ? this.renderComment() : this.renderList()}
            </Overlay>
        );
    }
}

export default WorkflowTransitionRequestReviewOverlay;
