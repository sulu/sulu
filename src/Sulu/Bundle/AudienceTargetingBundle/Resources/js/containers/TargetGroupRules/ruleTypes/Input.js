// @flow
import React from 'react';
import {Input as InputComponent} from 'sulu-admin-bundle/components';
import type {RuleTypeProps} from '../types';

export default class Input extends React.Component<RuleTypeProps> {
    handleChange = (value: ?string) => {
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
            },
            value,
        } = this.props;

        return (
            <InputComponent onChange={this.handleChange} value={value[name]} />
        );
    }
}
