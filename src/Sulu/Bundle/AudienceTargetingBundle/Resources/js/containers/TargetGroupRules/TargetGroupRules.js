// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Button, ButtonGroup, Table} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import ruleRegistry from './registries/ruleRegistry';
import RuleOverlay from './RuleOverlay';
import targetGroupRulesStyles from './targetGroupRules.scss';
import {getFrequencyTranslation} from './utils';
import type {Rule} from './types';

type Props = {|
    onChange: (value: Array<Rule>) => void,
    value: Array<Rule>,
|};

@observer
class TargetGroupRules extends React.Component<Props> {
    @observable showOverlay: boolean = false;
    @observable ruleIndex: number | typeof undefined = undefined;
    @observable selectedIndices: Array<number> = [];

    @action handlePlusButtonClick = () => {
        this.showOverlay = true;
        this.ruleIndex = undefined;
    };

    @action handleOverlayClose = () => {
        this.showOverlay = false;
        this.ruleIndex = undefined;
    };

    @action handleOverlayConfirm = (rule: Rule) => {
        const {onChange, value = []} = this.props;

        if (this.ruleIndex === undefined) {
            onChange([...value, rule]);
        } else {
            const newValue = [...value];
            newValue.splice(this.ruleIndex, 1, rule);
            onChange(newValue);
        }

        this.showOverlay = false;
        this.ruleIndex = undefined;
    };

    @action handleRemoveButtonClick = () => {
        const {onChange, value = []} = this.props;
        onChange(value.filter((rule, index) => !this.selectedIndices.includes(index)));
        this.selectedIndices.splice(0, this.selectedIndices.length);
    };

    @action handleAllSelectionChange = (checked: ?boolean) => {
        if (!checked) {
            this.selectedIndices.splice(0, this.selectedIndices.length);
        } else {
            const {value} = this.props;
            value.forEach((rule, index) => {
                if (!this.selectedIndices.includes(index)) {
                    this.selectedIndices.push(index);
                }
            });
        }
    };

    @action handleSelectionChange = (id: number, checked: ?boolean) => {
        if (checked && !this.selectedIndices.includes(id)) {
            this.selectedIndices.push(id);
        }

        if (!checked && this.selectedIndices.includes(id)) {
            this.selectedIndices.splice(this.selectedIndices.findIndex((value) => value === id), 1);
        }
    };

    @action handleEditClick = (rowId: string | number, index: number) => {
        this.ruleIndex = index;
        this.showOverlay = true;
    };

    render() {
        const {ruleIndex} = this;
        const {value} = this.props;

        return (
            <Fragment>
                <div className={targetGroupRulesStyles.buttons}>
                    <ButtonGroup>
                        <Button icon="su-plus" onClick={this.handlePlusButtonClick} />
                        <Button
                            disabled={this.selectedIndices.length === 0}
                            icon="su-trash-alt"
                            onClick={this.handleRemoveButtonClick}
                        />
                    </ButtonGroup>
                </div>
                <Table
                    buttons={[
                        {icon: 'su-pen', onClick: this.handleEditClick},
                    ]}
                    onAllSelectionChange={this.handleAllSelectionChange}
                    onRowSelectionChange={this.handleSelectionChange}
                    selectMode="multiple"
                >
                    <Table.Header>
                        <Table.HeaderCell>
                            {translate('sulu_admin.title')}
                        </Table.HeaderCell>
                        <Table.HeaderCell>
                            {translate('sulu_audience_targeting.assigned_at')}
                        </Table.HeaderCell>
                        <Table.HeaderCell>
                            {translate('sulu_audience_targeting.conditions')}
                        </Table.HeaderCell>
                    </Table.Header>
                    <Table.Body>
                        {value.map((rule, index) => (
                            <Table.Row key={index} selected={this.selectedIndices.includes(index)}>
                                <Table.Cell>{rule.title}</Table.Cell>
                                <Table.Cell>{getFrequencyTranslation(rule.frequency)}</Table.Cell>
                                <Table.Cell>
                                    {rule.conditions
                                        .map(
                                            (condition) => condition.type
                                                ? ruleRegistry.get(condition.type).name
                                                : undefined
                                        )
                                        .filter((conditionType) => conditionType)
                                        .join(' & ')
                                    }
                                </Table.Cell>
                            </Table.Row>
                        ))}
                    </Table.Body>
                </Table>
                <RuleOverlay
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.showOverlay}
                    value={ruleIndex !== undefined ? value[ruleIndex] : undefined}
                />
            </Fragment>
        );
    }
}

export default TargetGroupRules;
