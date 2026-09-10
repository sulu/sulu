// @flow
import jexl from 'jexl';
import {translate} from '../../../utils/Translator';
import {hasRequestWorkflow} from '../requestWorkflowConfig';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

// The request is applied after the save, so a published page is a draft again by the time it runs.
const PLACE_TO_TRANSITION = {
    draft: 'request_for_review_draft',
    published: 'request_for_review_draft',
    unpublished: 'request_for_review',
};

export default class RequestForPublishToolbarAction extends AbstractFormToolbarAction {
    getToolbarItemConfig() {
        const {
            disabled_condition: disabledCondition,
            visible_condition: visibleCondition,
        } = this.options;

        const visibleConditionFulfilled = !visibleCondition || jexl.evalSync(visibleCondition, this.conditionData);

        if (!visibleConditionFulfilled) {
            return;
        }

        const {data, dirty, id, resourceKey} = this.resourceFormStore;

        if (!id) {
            // Nothing is saved on the create form, so there is no workflow place to read and no
            // `workflowTransitionRequestEnabled` either. Only the picked template answers it, and
            // which templates are covered is the server's answer, taken from the admin config.
            if (!hasRequestWorkflow(resourceKey, data.template)) {
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
                this.form.submit({action: transition});
            },
            type: 'button',
        };
    }
}
