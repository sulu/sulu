// @flow
import {translate} from '../../../utils/Translator';
import type {WorkflowTransitionRequestApproval} from './types';

/**
 * Falls back to the raw key, so a project that adds its own check does not have to ship a
 * translation before the overlay reads sensibly.
 */
export function validatorName(validatorKey: string): string {
    const translationKey = 'sulu_content.workflow_transition_request.validators.' + validatorKey;
    const label = translate(translationKey);

    return label === translationKey ? validatorKey : label;
}

export function approverName(approval: WorkflowTransitionRequestApproval): string {
    if (approval.reviewer) {
        return approval.reviewer.fullName;
    }

    return translate('sulu_admin.unknown_user');
}
