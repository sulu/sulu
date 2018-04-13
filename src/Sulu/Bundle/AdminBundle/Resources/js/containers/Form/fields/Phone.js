// @flow
import React from 'react';
import Input from '../../../components/Input';
import type {FieldTypeProps} from '../../../types';

export default class Phone extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {error, value, onChange, onFinish} = this.props;

        return (
            <Input
                icon="su-phone"
                onChange={onChange}
                onBlur={onFinish}
                value={value}
                valid={!error}
            />
        );
    }
}
