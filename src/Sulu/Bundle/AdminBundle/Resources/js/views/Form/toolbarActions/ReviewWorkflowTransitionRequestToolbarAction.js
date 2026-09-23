// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import jexl from 'jexl';
import {translate} from '../../../utils/Translator';
import ResourceRequester from '../../../services/ResourceRequester';
import userStore from '../../../stores/userStore';
import WorkflowTransitionRequestReviewOverlay from '../components/WorkflowTransitionRequestReviewOverlay';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';
import type {ApprovalStatus} from '../components/types';

export default class ReviewWorkflowTransitionRequestToolbarAction extends AbstractFormToolbarAction {
    @observable open: boolean = false;

    /**
     * What the user may do with the request is the server's answer, carried on the request itself:
     * articles and snippets have no permissions of their own the form could read.
     */
    @computed get permissions(): {[string]: boolean} {
        return this.resourceFormStore.data.activeWorkflowTransitionRequest?.permissions || {};
    }

    /**
     * Deciding takes the review permission and a request somebody else made: the overlay is also open
     * to editors, who come for the retry rather than for a verdict.
     */
    @computed get canAct(): boolean {
        if (!this.permissions.review) {
            return false;
        }

        const creatorId = this.resourceFormStore.data.activeWorkflowTransitionRequest?.createdBy?.id;

        return String(creatorId) !== String(userStore.user?.id);
    }

    /**
     * One answer for both publish buttons, because the server draws the same line: `live` publishes
     * whenever, `edit` only carries out what the reviewers approved.
     */
    @computed get canPublish(): boolean {
        return !!this.permissions.publish;
    }

    /** Re-running a failed check is how the content gets fixed, so it takes the edit permission. */
    @computed get canRetry(): boolean {
        return !!this.permissions.retry;
    }

    @computed get userDecision(): ?ApprovalStatus {
        const userId = userStore.user?.id;
        if (userId === undefined || userId === null) {
            return undefined;
        }

        const approvals = this.resourceFormStore.data.activeWorkflowTransitionRequest?.approvals || [];
        // A deleted reviewer leaves the row without a user, which must not match a missing one.
        const ownRow = approvals.find(
            (approval) => approval.reviewer && String(approval.reviewer.id) === String(userId)
        );

        return ownRow ? ownRow.status : undefined;
    }

    /** Resolving the request is the whole point of the lock, so this action survives it. */
    get enabledWhileLocked(): boolean {
        return true;
    }

    @action handleOpen = () => {
        this.open = true;
    };

    @action handleClose = () => {
        this.open = false;
    };

    handleApprove = async(comment: ?string) => {
        const request = this.resourceFormStore.data.activeWorkflowTransitionRequest;
        if (!request) {
            return;
        }

        await ResourceRequester.post(
            'workflow_transition_requests',
            {comment: comment || null},
            {id: request.id, action: 'approve'}
        );
        // The decision is stored on the request, which the form only sees through the resource.
        this.resourceFormStore.resourceStore.reload();
    };

    handleReject = async(comment: string) => {
        const request = this.resourceFormStore.data.activeWorkflowTransitionRequest;
        if (!request) {
            return;
        }

        await ResourceRequester.post(
            'workflow_transition_requests',
            {comment},
            {id: request.id, action: 'reject'}
        );
        this.resourceFormStore.resourceStore.reload();
    };

    handleRetry = async(validatorKey: string) => {
        const request = this.resourceFormStore.data.activeWorkflowTransitionRequest;
        if (!request) {
            return;
        }

        await ResourceRequester.post(
            'workflow_transition_requests',
            {},
            {id: request.id, action: 'retry', validator: validatorKey}
        );
        this.resourceFormStore.resourceStore.reload();
    };

    handlePublish = () => {
        this.handleClose();
        this.form.trigger('publish');
    };

    getNode(index: ?number) {
        const request = this.resourceFormStore.data.activeWorkflowTransitionRequest;
        if (!request) {
            return null;
        }

        return (
            <WorkflowTransitionRequestReviewOverlay
                canAct={this.canAct}
                canPublish={this.canPublish}
                canRetry={this.canRetry}
                key={`workflow-transition-request-review-${index ?? 0}`}
                onApprove={this.handleApprove}
                onClose={this.handleClose}
                onPublish={this.handlePublish}
                onReject={this.handleReject}
                onRetry={this.handleRetry}
                open={this.open}
                request={request}
                userDecision={this.userDecision}
            />
        );
    }

    getToolbarItemConfig() {
        const {
            disabled_condition: disabledCondition,
            visible_condition: visibleCondition,
        } = this.options;

        const visibleConditionFulfilled = !visibleCondition || jexl.evalSync(visibleCondition, this.conditionData);

        if (!visibleConditionFulfilled) {
            return;
        }

        if (!this.resourceFormStore.data.activeWorkflowTransitionRequest) {
            return;
        }

        const disabled = disabledCondition ? jexl.evalSync(disabledCondition, this.conditionData) : false;

        return {
            label: translate('sulu_content.workflow_transition_request.review_action'),
            disabled,
            onClick: this.handleOpen,
            type: 'button',
        };
    }
}
