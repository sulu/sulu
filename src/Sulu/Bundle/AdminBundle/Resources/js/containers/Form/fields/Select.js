// @flow
import React from 'react';
import {computed, makeObservable} from 'mobx';
import MultiSelectComponent from '../../../components/MultiSelect';
import type {FieldTypeProps} from '../../../types';
import type {IObservableArray} from 'mobx';

type Props = FieldTypeProps<?Array<string | number>>;

function isArrayLike(value: mixed): boolean {
    return !!value
        && typeof value === 'object'
        && typeof value.length === 'number'
        && typeof value.forEach === 'function';
}

export default class Select extends React.Component<Props> {
    constructor(props: FieldTypeProps<?Array<string | number>>) {
        super(props);
        if (typeof makeObservable === 'function') {
            makeObservable(this);
        }

        const {onChange, schemaOptions, value} = this.props;

        const {
            default_values: {
                value: defaultOptions,
            } = {},
        } = schemaOptions;

        if (defaultOptions === undefined || defaultOptions === null) {
            return;
        }

        if (!Array.isArray(defaultOptions) && !isArrayLike(defaultOptions)) {
            throw new Error('The "default_values" schema option must be an array!');
        }

        const normalizedDefaultOptions = Array.isArray(defaultOptions)
            ? defaultOptions
            : Array.from((defaultOptions: any));
        const defaultValues = normalizedDefaultOptions.map(({name: defaultValue}) => {
            if (typeof defaultValue !== 'number' && typeof defaultValue !== 'string') {
                throw new Error('A single schema option of "default_values" must be a string or number');
            }

            return defaultValue;
        });

        if (value === undefined) {
            onChange(defaultValues, {isDefaultValue: true});
        }
    }

    @computed get values(): Array<any> | IObservableArray<any> {
        const {values} = this.props.schemaOptions;

        if (!values) {
            throw new Error('The "values" option has to be set for the Select FieldType');
        }

        const schemaValues = values.value;
        if (!Array.isArray(schemaValues) && !isArrayLike(schemaValues)) {
            throw new Error('The "values" option has to be set for the Select FieldType');
        }

        return Array.isArray(schemaValues) ? schemaValues : Array.from((schemaValues: any));
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
