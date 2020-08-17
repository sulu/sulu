// @flow
import React from 'react';
import {computed} from 'mobx';
import MultiSelectComponent from '../../../components/MultiSelect';
import type {FieldTypeProps} from '../../../types';

type Props = FieldTypeProps<?Array<string | number>>;

export default class Select extends React.Component<Props> {
    constructor(props: FieldTypeProps<?Array<string | number>>) {
        super(props);

        const {onChange, schemaOptions, value} = this.props;

        const {
            default_values: {
                value: defaultOptions,
            } = {},
        } = schemaOptions;

        if (defaultOptions === undefined || defaultOptions === null) {
            return;
        }

        if (!Array.isArray(defaultOptions)) {
            throw new Error('The "default_values" schema option must be an array!');
        }

        const defaultValues = defaultOptions.map(({name: defaultValue}) => {
            if (typeof defaultValue !== 'number' && typeof defaultValue !== 'string') {
                throw new Error('A single schema option of "default_values" must be a string or number');
            }

            return defaultValue;
        });

        if (value === undefined) {
            onChange(defaultValues);
        }
    }

    @computed get values() {
        const {values} = this.props.schemaOptions;

        if (!values || !Array.isArray(values.value)) {
            throw new Error('The "values" option has to be set for the Select FieldType');
        }

        return values.value;
    }

    handleChange = (value: Array<string | number>) => {
        const {onChange, onFinish} = this.props;

        const allowedValues = this.values.map((value) => value.name);
        const filteredValue = value.filter((v) => allowedValues.includes(v));

        onChange(filteredValue.length > 0 ? filteredValue : undefined);
        onFinish();
    };

    render() {
        const {disabled, value} = this.props;

        return (
            <MultiSelectComponent disabled={!!disabled} onChange={this.handleChange} values={value || []}>
                {this.values.map(({name: value, title}) => {
                    if (typeof value !== 'string' && typeof value !== 'number') {
                        throw new Error('The children of "values" must only contain values of type string or number!');
                    }

                    return (
                        <MultiSelectComponent.Option key={value} value={value}>
                            {title}
                        </MultiSelectComponent.Option>
                    );
                })}
            </MultiSelectComponent>
        );
    }
}
