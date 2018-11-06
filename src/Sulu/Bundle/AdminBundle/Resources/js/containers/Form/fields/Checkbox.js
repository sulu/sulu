// @flow
import React from 'react';
import CheckboxComponent from '../../../components/Checkbox';
import Toggler from '../../../components/Toggler';
import type {FieldTypeProps} from '../../../types';

export default class Checkbox extends React.Component<FieldTypeProps<boolean>> {
    handleChange = (checked: boolean) => {
        const {onChange, onFinish} = this.props;
        onChange(checked);
        onFinish();
    };

    render() {
        const {
            disabled,
            schemaOptions: {
                label: {
                    title: label,
                } = {},
                type: {
                    value: type,
                } = {},
            } = {},
            value,
        } = this.props;

        if (type === 'toggler') {
            return <Toggler checked={!!value} onChange={this.handleChange}>{label}</Toggler>;
        }

        return (
            <CheckboxComponent
                active={!disabled}
                checked={!!value}
                onChange={this.handleChange}
            >{label}</CheckboxComponent>
        );
    }
}
