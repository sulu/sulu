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
            schemaOptions,
            value,
        } = this.props;

        if (schemaOptions && schemaOptions.type && schemaOptions.type.value === 'toggler') {
            return <Toggler checked={!!value} onChange={this.handleChange} />;
        }

        return <CheckboxComponent checked={!!value} onChange={this.handleChange} />;
    }
}
