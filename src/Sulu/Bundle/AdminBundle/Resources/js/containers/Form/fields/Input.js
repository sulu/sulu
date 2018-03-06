// @flow
import React from 'react';
import InputComponent from '../../../components/Input';
import type {FieldTypeProps} from '../../../types';

export default class Input extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {error, value, onChange, onFinish} = this.props;

        return (
            <InputComponent
                onChange={onChange}
                onFinish={onFinish}
                value={value}
                valid={!error}
            />
        );
    }
}
