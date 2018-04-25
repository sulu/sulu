// @flow
import React from 'react';
import {default as PhoneComponent} from '../../../components/Phone';
import type {FieldTypeProps} from '../../../types';

export default class Phone extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {error, value, onChange, onFinish} = this.props;

        return (
            <PhoneComponent
                onChange={onChange}
                onBlur={onFinish}
                value={value}
                valid={!error}
            />
        );
    }
}
