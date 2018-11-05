// @flow
import React from 'react';
import {default as ColorPickerComponent} from '../../../components/ColorPicker';
import type {FieldTypeProps} from '../../../types';

export default class ColorPicker extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {dataPath, disabled, error, onChange, onFinish, value} = this.props;

        return (
            <ColorPickerComponent
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
