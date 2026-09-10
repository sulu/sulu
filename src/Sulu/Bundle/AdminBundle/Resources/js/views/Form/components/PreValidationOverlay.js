// @flow
import React from 'react';
import Button from '../../../components/Button';
import Overlay from '../../../components/Overlay';
import {translate} from '../../../utils/Translator';
import WorkflowCheckCard from './WorkflowCheckCard';
import {renderMessages} from './decisionMessages';
import workflowCheckCardStyles from './workflowCheckCard.scss';
import type {CheckRow} from './WorkflowCheckCard';
import type {PreValidationResult} from './types';

type Props = {|
    onClose: () => void,
    open: boolean,
    results: Array<PreValidationResult>,
|};

function preValidatorName(key: string): string {
    const translationKey = 'sulu_content.workflow_transition_request.pre_validators.' + key;
    const label = translate(translationKey);

    return label === translationKey ? key : label;
}

function buildRows(results: Array<PreValidationResult>): Array<CheckRow> {
    return results.map((result) => ({
        caption: result.passed
            ? translate('sulu_content.workflow_transition_request.pre_validator_passed')
            : renderMessages(result.messages) || '',
        id: result.key,
        status: result.passed ? 'approved' : 'rejected',
        title: preValidatorName(result.key),
    }));
}

/**
 * Reads like the review overlay, because it answers the same question: which checks does this
 * content still have to pass. Here they are the workflow's pre-validators, so the list holds
 * however many are configured.
 */
export default class PreValidationOverlay extends React.Component<Props> {
    renderFooter() {
        const {onClose} = this.props;

        return (
            <div className={workflowCheckCardStyles.footerActions}>
                <Button className={workflowCheckCardStyles.confirmButton} onClick={onClose} skin="primary">
                    {translate('sulu_admin.ok')}
                </Button>
            </div>
        );
    }

    render() {
        const {onClose, open, results} = this.props;
        const passed = results.filter((result) => result.passed).length;

        return (
            <Overlay
                footer={this.renderFooter()}
                onClose={onClose}
                open={open}
                title={translate('sulu_content.workflow_transition_request.pre_validation_failed_title')}
            >
                <div className={workflowCheckCardStyles.body}>
                    <WorkflowCheckCard
                        headerCount={translate(
                            'sulu_content.workflow_transition_request.n_of_m_checks_passed',
                            {passed, total: results.length}
                        )}
                        headerText={translate('sulu_content.workflow_transition_request.pre_validation_failed_text')}
                        rows={buildRows(results)}
                    />
                </div>
            </Overlay>
        );
    }
}
