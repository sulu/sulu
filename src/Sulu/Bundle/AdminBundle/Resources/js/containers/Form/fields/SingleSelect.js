// @flow
import React from 'react';
import {toJS} from 'mobx';
import SingleSelectComponent from '../../../components/SingleSelect';
import type {FieldTypeProps} from '../../../types';

export default class SingleSelect extends React.Component<FieldTypeProps<string | number>> {
    constructor(props: FieldTypeProps<string | number>) {
        super(props);

        const {onChange, schemaOptions, value} = this.props;

        const {
            default_value: {
                value: defaultValue,
            } = {},
        } = schemaOptions;

        if (defaultValue === undefined || defaultValue === null || defaultValue === '') {
            return;
        }

        if (typeof defaultValue !== 'number' && typeof defaultValue !== 'string') {
            throw new Error('The "default_value" schema option must be a string or a number!');
        }

        if (value === undefined) {
            onChange(defaultValue, {isDefaultValue: true});
        }
    }

    handleChange = (value: string | number) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {schemaOptions, disabled, value} = this.props;
        const values = toJS(schemaOptions.values);

        if (!values || !Array.isArray(values.value)) {
            throw new Error('The "values" schema option of the SingleSelect field-type must be an array!');
        }

        return (
            <SingleSelectComponent disabled={!!disabled} onChange={this.handleChange} value={value}>
                {values.value.map(({name: value, title}, index) => {
                    if (typeof value !== 'string' && typeof value !== 'number' && value !== undefined) {
                        throw new Error(
                            'The children of "values" must only contain values of type string, number or undefined!'
                        );
                    }

                    // it is not possible to define a param without a name in a form xml. to allow for creating an
                    // empty option, we use undefined as value if the name of a param is an empty string in the xml
                    const normalizedValue = value === '' ? undefined : value;

                    return (
                        <SingleSelectComponent.Option key={index} value={normalizedValue}>
                            {title || value}
                        </SingleSelectComponent.Option>
                    );
                })}
            </SingleSelectComponent>
        );
    }
}
