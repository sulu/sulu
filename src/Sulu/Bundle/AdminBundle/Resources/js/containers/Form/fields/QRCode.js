// @flow
import React from 'react';
import QRCodeComponent from '../../../components/QRCode';
import type {FieldTypeProps} from '../types';

export default class Input extends React.Component<FieldTypeProps<?string>> {
    handleBlur = () => {
        this.props.onFinish();
    };

    render() {
        const {
            dataPath,
            error,
            disabled,
            onChange,
            value,
        } = this.props;

        return (
            <QRCodeComponent
                disabled={!!disabled}
                id={dataPath}
                onBlur={this.handleBlur}
                onChange={onChange}
                valid={!error}
                value={value}
            />
        );
    }
}
