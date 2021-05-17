// @flow
import React from 'react';
import BicComponent from '../../../components/Bic';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';

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
