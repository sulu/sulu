// @flow
import React from 'react';
import {Input} from 'sulu-admin-bundle/components';
import type {RuleTypeProps} from '../types';
import keyValueStyles from './keyValue.scss';

export default class KeyValue extends React.Component<RuleTypeProps> {
    handleParameterChange = (parameter: ?string) => {
        const {onChange, options} = this.props;
        onChange({...this.props.value, [options.keyName]: parameter});
    };

    handleValueChange = (value: ?string) => {
        const {onChange, options} = this.props;
        onChange({...this.props.value, [options.valueName]: value});
    };

    render() {
        const {options, value} = this.props;
        const {keyName, keyPlaceholder, valueName, valuePlaceholder} = options;

        return (
            <div className={keyValueStyles.inputs}>
                <Input onChange={this.handleParameterChange} placeholder={keyPlaceholder} value={value[keyName]} />
                <Input onChange={this.handleValueChange} placeholder={valuePlaceholder} value={value[valueName]} />
            </div>
        );
    }
}
