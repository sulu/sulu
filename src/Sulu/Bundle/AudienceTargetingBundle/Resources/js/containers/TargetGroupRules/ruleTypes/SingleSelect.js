// @flow
import React from 'react';
import {SingleSelect as SingleSelectComponent} from 'sulu-admin-bundle/components';
import type {RuleTypeProps} from '../types';

export default class SingleSelect extends React.Component<RuleTypeProps> {
    handleChange = (value: string) => {
        const {
            onChange,
            options: {
                name,
            },
        } = this.props;

        onChange({[name]: value});
    };

    render() {
        const {
            options: {
                name,
                options,
            },
            value,
        } = this.props;

        return (
            <SingleSelectComponent onChange={this.handleChange} value={value[name]}>
                {options.map((option) => (
                    <SingleSelectComponent.Option key={option.id} value={option.id}>
                        {option.name}
                    </SingleSelectComponent.Option>
                ))}
            </SingleSelectComponent>
        );
    }
}
