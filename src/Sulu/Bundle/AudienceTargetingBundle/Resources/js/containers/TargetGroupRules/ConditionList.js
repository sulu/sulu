// @flow
import React, {Fragment} from 'react';
import {Button} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import Condition from './Condition';
import type {Condition as ConditionType} from './types';

type Props = {|
    onChange: (value: Array<ConditionType>) => void,
    value: Array<ConditionType>,
|};

export default class ConditionList extends React.Component<Props> {
    handleAddClick = () => {
        const {onChange, value} = this.props;
        onChange([...value, {condition: {}, type: undefined}]);
    };

    handleChange = (condition: ConditionType, index: number) => {
        const {onChange, value} = this.props;

        const newValue = [...value];
        newValue[index] = condition;

        onChange(newValue);
    };

    handleRemove = (removeIndex: number) => {
        const {onChange, value} = this.props;
        onChange(value.filter((condition, index) => index !== removeIndex));
    };

    render() {
        const {value} = this.props;

        return (
            <Fragment>
                {value.map((condition, index) => (
                    <Condition
                        index={index}
                        key={index}
                        onChange={this.handleChange}
                        onRemove={this.handleRemove}
                        value={value[index]}
                    />
                ))}
                <Button icon="su-plus" onClick={this.handleAddClick} skin="secondary">
                    {translate('sulu_audience_targeting.add_condition')}
                </Button>
            </Fragment>
        );
    }
}
