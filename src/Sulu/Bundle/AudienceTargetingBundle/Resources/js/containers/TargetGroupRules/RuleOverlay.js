// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Form, Input, Overlay, SingleSelect} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import ruleOverlayStyles from './ruleOverlay.scss';
import ConditionList from './ConditionList';
import {getFrequencyTranslation} from './utils';
import type {Condition, Rule} from './types';

type Props = {|
    onClose: () => void,
    onConfirm: (value: Rule) => void,
    open: boolean,
    value: ?Rule,
|};

@observer
class RuleOverlay extends React.Component<Props> {
    @observable title: ?string = undefined;
    @observable frequency: ?number = undefined;
    @observable conditions: ?Array<Condition> = undefined;
    @observable showTitleError: boolean = false;
    @observable showFrequencyError: boolean = false;

    @action componentDidUpdate(prevProps: Props) {
        if (prevProps.open === false && this.props.open === true) {
            const {value} = this.props;

            this.showTitleError = false;
            this.showFrequencyError = false;

            if (value) {
                this.title = value.title;
                this.frequency = value.frequency;
                this.conditions = value.conditions;
            } else {
                this.title = undefined;
                this.frequency = undefined;
                this.conditions = undefined;
            }
        }
    }

    @action handleTitleChange = (title: ?string) => {
        this.title = title;
    };

    @action handleTitleBlur = () => {
        this.validateTitle();
    };

    @action handleFrequencyChange = (frequency: number) => {
        this.frequency = frequency;
        this.validateFrequency();
    };

    @action handleConditionChange = (conditions: Array<Condition>) => {
        this.conditions = conditions;
    };

    @action handleConfirm = () => {
        if (!this.validate() || !this.title || !this.frequency) {
            return;
        }

        const {onConfirm} = this.props;
        onConfirm({
            conditions: this.conditions || [],
            frequency: this.frequency,
            title: this.title,
        });
    };

    @action validateTitle = () => {
        this.showTitleError = !this.title;
    };

    @action validateFrequency = () => {
        this.showFrequencyError = !this.frequency;
    };

    @action validate = () => {
        this.validateTitle();
        this.validateFrequency();

        return !this.showTitleError && !this.showFrequencyError;
    };

    render() {
        const {onClose, open} = this.props;

        return (
            <Overlay
                confirmText={translate('sulu_admin.ok')}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="small"
                title={translate('sulu_audience_targeting.configure_rule')}
            >
                <div className={ruleOverlayStyles.overlay}>
                    <Form>
                        <Form.Field
                            error={this.showTitleError ? translate('sulu_admin.error_required') : undefined}
                            label={translate('sulu_admin.title')}
                            required={true}
                        >
                            <Input onBlur={this.handleTitleBlur} onChange={this.handleTitleChange} value={this.title} />
                        </Form.Field>
                        <Form.Field
                            error={this.showFrequencyError ? translate('sulu_admin.error_required') : undefined}
                            label={translate('sulu_audience_targeting.assigned_at')}
                            required={true}
                        >
                            <SingleSelect onChange={this.handleFrequencyChange} value={this.frequency}>
                                <SingleSelect.Option value={1}>
                                    {getFrequencyTranslation(1)}
                                </SingleSelect.Option>
                                <SingleSelect.Option value={2}>
                                    {getFrequencyTranslation(2)}
                                </SingleSelect.Option>
                                <SingleSelect.Option value={3}>
                                    {getFrequencyTranslation(3)}
                                </SingleSelect.Option>
                            </SingleSelect>
                        </Form.Field>
                        <Form.Field
                            description={translate('sulu_audience_targeting.conditions_info_text')}
                            label={translate('sulu_audience_targeting.conditions')}
                        >
                            <ConditionList onChange={this.handleConditionChange} value={this.conditions || []} />
                        </Form.Field>
                    </Form>
                </div>
            </Overlay>
        );
    }
}

export default RuleOverlay;
