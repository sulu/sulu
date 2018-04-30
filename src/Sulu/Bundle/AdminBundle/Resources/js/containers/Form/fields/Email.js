// @flow
import React from 'react';
import {default as EmailComponent} from '../../../components/Email';
import type {FieldTypeProps} from '../../../types';

export default class Email extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {error, onChange, onFinish, value} = this.props;

        return (
            <EmailComponent
                onChange={onChange}
                onBlur={onFinish}
                valid={!error}
                value={value}
            />
        );
    }
}
