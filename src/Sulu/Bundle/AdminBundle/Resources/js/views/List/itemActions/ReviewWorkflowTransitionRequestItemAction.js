// @flow
import React from 'react';
import {action, observable} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import snackbarStore from '../../../stores/snackbarStore';
import {translate} from '../../../utils/Translator';
import WorkflowTransitionRequestReviewOverlay from '../../Form/components/WorkflowTransitionRequestReviewOverlay';
import AbstractListItemAction from './AbstractListItemAction';
import type {WorkflowTransitionRequestData} from '../../Form/components/types';
import type {Node} from 'react';

export default class ReviewWorkflowTransitionRequestItemAction extends AbstractListItemAction {
    @observable request: ?WorkflowTransitionRequestData = undefined;

    requestedId: ?string = undefined;

    /** The row only carries requester and status, the overlay needs the reviewers of that request. */
    handleClick = (id: string) => {
        this.requestedId = id;

        ResourceRequester.get('workflow_transition_requests', {id})
            .then(action((request) => {
                if (this.requestedId === id) {
                    this.request = request;
                }
            }))
            .catch((error) => {
                if (this.requestedId === id) {
                    snackbarStore.add({text: error.detail || translate('sulu_admin.error'), type: 'error'}, 4000);
                }
            });
    };

    @action handleClose = () => {
        this.requestedId = undefined;
        this.request = undefined;
    };

    getItemActionConfig(item: ?Object) {
        return {
            icon: 'su-information',
            onClick: item?.id ? () => this.handleClick(item.id) : undefined,
            disabled: !item?.id,
        };
    }

    getNode(): Node {
        const request = this.request;

        if (!request) {
            return null;
        }

        return (
            <WorkflowTransitionRequestReviewOverlay
                canAct={false}
                key="review_workflow_transition_request"
                mode="view"
                onClose={this.handleClose}
                open={true}
                request={request}
            />
        );
    }
}
