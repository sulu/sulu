// @flow
import React from 'react';
import IbanComponent from '../../../components/Iban';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';

export default class Iban extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {dataPath, disabled, error, onChange, onFinish, value} = this.props;

        return (
            <IbanComponent
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
