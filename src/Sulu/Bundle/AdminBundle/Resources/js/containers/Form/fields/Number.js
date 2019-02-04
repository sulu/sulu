// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import NumberComponent from '../../../components/Number';
import type {FieldTypeProps, SchemaOptions} from '../types';

@observer
export default class Number extends React.Component<FieldTypeProps<?number>> {
    @computed get schemaOptions(): SchemaOptions {
        const {schemaOptions} = this.props;

        if (!schemaOptions) {
            return {};
        }

        return schemaOptions;
    }

    @computed get min(): ?number {
        return this.schemaOptions.min ? parseFloat(this.schemaOptions.min.value) : undefined;
    }

    @computed get max(): ?number {
        return this.schemaOptions.max ? parseFloat(this.schemaOptions.max.value) : undefined;
    }

    @computed get step(): ?number {
        return this.schemaOptions.step ? parseFloat(this.schemaOptions.step.value) : undefined;
    }

    handleBlur = () => {
        this.props.onFinish();
    };

    render() {
        const {dataPath, disabled, error, onChange, value} = this.props;

        return (
            <NumberComponent
                disabled={!!disabled}
                id={dataPath}
                max={this.max}
                min={this.min}
                onBlur={this.handleBlur}
                onChange={onChange}
                step={this.step}
                valid={!error}
                value={value}
            />
        );
    }
}
