// @flow
import React from 'react';
import InputComponent from '../../../components/Input';
import type {FieldTypeProps} from '../../../types';

export default class Input extends React.Component<FieldTypeProps<?string>> {
    handleBlur = () => {
        const {onFinish} = this.props;
        if (onFinish) {
            onFinish();
        }
    };

    render() {
        const {error, onChange, value} = this.props;

        return (
            <InputComponent
                onChange={onChange}
                onBlur={this.handleBlur}
                valid={!error}
                value={value}
            />
        );
    }
}
