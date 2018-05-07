// @flow
import React from 'react';
import CheckboxComponent from '../../../components/Checkbox';
import type {FieldTypeProps} from '../../../types';

export default class Checkbox extends React.Component<FieldTypeProps<boolean>> {
    handleChange = (checked: boolean) => {
        const {onChange, onFinish} = this.props;
        onChange(checked);

        if (onFinish) {
            onFinish();
        }
    };

    render() {
        const {value} = this.props;

        return <CheckboxComponent checked={!!value} onChange={this.handleChange} />;
    }
}
