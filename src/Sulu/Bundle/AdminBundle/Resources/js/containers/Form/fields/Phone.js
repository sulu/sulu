// @flow
import React from 'react';
import PhoneComponent from '../../../components/Phone';
import type {FieldTypeProps} from '../../../types';

export default class Phone extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {dataPath, disabled, error, onChange, onFinish, value} = this.props;

        return (
            <PhoneComponent
                disabled={!!disabled}
                id={dataPath}
                onBlur={onFinish}
                onChange={onChange}
                valid={!error}
                value={value}
            />
        );
    }
}
