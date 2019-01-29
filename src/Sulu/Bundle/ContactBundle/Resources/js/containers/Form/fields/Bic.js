// @flow
import React from 'react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import BicComponent from '../../../components/Bic';

export default class Bic extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {dataPath, disabled, error, onChange, onFinish, value} = this.props;

        return (
            <BicComponent
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
