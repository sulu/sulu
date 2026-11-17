// @flow
import React from 'react';
import {action, observable, toJS, when} from 'mobx';
import jexl from 'jexl';
import {translate} from '../../../utils/Translator';
import FormOverlay from '../../../containers/FormOverlay';
import MemoryFormStoreFactory from '../../../containers/Form/stores/memoryFormStoreFactory';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

// A bundle adds its fields by extending this form. Without fields no overlay opens.
const REQUEST_FORM_KEY = 'workflow_transition_request_details';
const REQUEST_DATA_KEY = 'workflowTransitionRequestData';

// The request is applied after the save, so a published page is a draft again by the time it runs.
const PLACE_TO_TRANSITION = {
    draft: 'request_for_review_draft',
    published: 'request_for_review_draft',
    unpublished: 'request_for_review',
};

export default class RequestForPublishToolbarAction extends AbstractFormToolbarAction {
    @observable open: boolean = false;
    @observable pendingTransition: ?string = undefined;
    @observable requestFormStore = MemoryFormStoreFactory.createFromFormKey(REQUEST_FORM_KEY);

    getToolbarItemConfig() {
        const {
            disabled_condition: disabledCondition,
            templates,
            visible_condition: visibleCondition,
        } = this.options;

        const visibleConditionFulfilled = !visibleCondition || jexl.evalSync(visibleCondition, this.conditionData);

        if (!visibleConditionFulfilled) {
            return;
        }

        const {data, dirty, id} = this.resourceFormStore;

        if (!id) {
            // Nothing is saved yet, so the picked template answers, against the list the action carries.
            if (!Array.isArray(templates) || !templates.includes(data.template)) {
                return;
            }

            // What the create form will save is unpublished.
            return this.buildItemConfig('unpublished', dirty, disabledCondition);
        }

        // Saved content carries the answer, normalized off the template it was saved with.
        if (!data.workflowTransitionRequestEnabled) {
            return;
        }

        return this.buildItemConfig(data.workflowPlace, dirty, disabledCondition);
    }

    buildItemConfig(place: mixed, dirty: boolean, disabledCondition: mixed) {
        const transition = typeof place === 'string' ? PLACE_TO_TRANSITION[place] : undefined;

        if (!transition) {
            return;
        }

        const extraDisabled = disabledCondition ? jexl.evalSync(disabledCondition, this.conditionData) : false;

        return {
            label: translate('sulu_content.workflow_transition_request.request_for_publish'),
            // A published page without changes has nothing to send to review.
            disabled: (place === 'published' && !dirty) || extraDisabled,
            onClick: () => {
                this.handleClick(transition);
            },
            type: 'button',
        };
    }

    handleClick(transition: string) {
        when(
            () => !this.requestFormStore.loading,
            action(() => {
                if (Object.keys(this.requestFormStore.schema).length === 0) {
                    this.submit(transition, {});

                    return;
                }

                this.pendingTransition = transition;
                this.open = true;
            })
        );
    }

    // The data travels in the body of the save, which the server takes out of the content again.
    submit(transition: string, data: Object) {
        this.resourceFormStore.change(REQUEST_DATA_KEY, data);
        this.form.submit({action: transition});
    }

    @action handleConfirm = () => {
        const transition = this.pendingTransition;
        const data = toJS(this.requestFormStore.data);

        this.handleClose();

        if (transition) {
            this.submit(transition, data);
        }
    };

    @action handleClose = () => {
        this.open = false;
        this.pendingTransition = undefined;
        this.requestFormStore = MemoryFormStoreFactory.createFromFormKey(REQUEST_FORM_KEY);
    };

    getNode(index: ?number) {
        return (
            <FormOverlay
                confirmText={translate('sulu_admin.confirm')}
                formStore={this.requestFormStore}
                key={`request-for-publish-${index ?? 0}`}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={this.open}
                size="small"
                title={translate('sulu_content.workflow_transition_request.request_for_publish')}
            />
        );
    }
}
