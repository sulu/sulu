// @flow
import React from 'react';
import NumberComponent from '../../../components/Number';
import type {FieldTypeProps} from '../../../types';

export default class Number extends React.Component<FieldTypeProps<?number>> {
    options = {
        min: undefined,
        max: undefined,
        step: undefined,
    };

    constructor(props: FieldTypeProps<?number>) {
        super(props);

        const {schemaOptions} = this.props;

        if (!schemaOptions) {
            return;
        }

        if (schemaOptions.min) {
            this.options.min = parseFloat(schemaOptions.min.value);
        }
        if (schemaOptions.max) {
            this.options.max = parseFloat(schemaOptions.max.value);
        }
        if (schemaOptions.step) {
            this.options.step = parseFloat(schemaOptions.step.value);
        }
    }

    render() {
        const {error, onChange, onFinish, value} = this.props;

        return (
            <NumberComponent
                min={this.options.min}
                max={this.options.max}
                step={this.options.step}
                onChange={onChange}
                onBlur={onFinish}
                valid={!error}
                value={value}
            />
        );
    }
}
