// @flow
import React from 'react';
import InputComponent from '../../../components/Input';
import type {FieldTypeProps} from '../../../types';

export default class Input extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {error, onChange, onFinish, value} = this.props;

        return (
            <InputComponent
                onChange={onChange}
                onBlur={onFinish}
                valid={!error}
                value={value}
            />
        );
    }
}
