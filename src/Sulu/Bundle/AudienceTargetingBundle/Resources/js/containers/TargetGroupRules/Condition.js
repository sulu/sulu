// @flow
import React from 'react';
import {Button, SingleSelect} from 'sulu-admin-bundle/components';
import ruleRegistry from './registries/RuleRegistry';
import ruleTypeRegistry from './registries/RuleTypeRegistry';
import conditionStyles from './condition.scss';
import type {Condition as ConditionType} from './types';

type Props = {|
    index: number,
    onChange: (value: ConditionType, index: number) => void,
    onRemove: (index: number) => void,
    value: ConditionType,
|};

class Condition extends React.Component<Props> {
    handleRuleTypeChange = (rule: string) => {
        const {index, onChange, value} = this.props;
        onChange({...value, type: rule}, index);
    };

    handleRuleChange = (condition: ConditionType) => {
        const {index, onChange, value} = this.props;
        onChange({...value, condition}, index);
    };

    handleRemove = () => {
        const {index, onRemove} = this.props;
        onRemove(index);
    };

    render() {
        const {value} = this.props;

        const type = value.type ? ruleRegistry.get(value.type).type : undefined;
        const RuleType = type ? ruleTypeRegistry.get(type.name) : undefined;

        return (
            <div className={conditionStyles.conditionContainer}>
                <div className={conditionStyles.condition}>
                    <div className={conditionStyles.select}>
                        <SingleSelect onChange={this.handleRuleTypeChange} value={value.type}>
                            {Object.keys(ruleRegistry.getAll()).map((ruleKey) => (
                                <SingleSelect.Option key={ruleKey} value={ruleKey}>
                                    {ruleRegistry.get(ruleKey).name}
                                </SingleSelect.Option>
                            ))}
                        </SingleSelect>
                    </div>
                    <div className={conditionStyles.type}>
                        {!!RuleType &&
                            <RuleType
                                onChange={this.handleRuleChange}
                                options={type && type.options}
                                value={value.condition}
                            />
                        }
                    </div>
                </div>
                <Button className={conditionStyles.icon} icon="su-trash-alt" onClick={this.handleRemove} skin="icon" />
            </div>
        );
    }
}

export default Condition;
