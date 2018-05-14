// @flow
import React from 'react';
import {default as ColorPickerComponent} from '../../../components/ColorPicker';
import type {FieldTypeProps} from '../../../types';

export default class ColorPicker extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {error, onChange, onFinish, value} = this.props;

        return (
            <ColorPickerComponent
                onChange={onChange}
                onBlur={onFinish}
                valid={!error}
                value={value}
            />
        );
    }
}
