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

        if (defaultValue === undefined || defaultValue === null) {
            return;
        }

        if (typeof defaultValue !== 'number' && typeof defaultValue !== 'string') {
            throw new Error('The "default_value" schema option must be a string or a number!');
        }

        if (value === undefined) {
            onChange(defaultValue);
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
            throw new Error('The "values" option has to be set for the SingleSelect FieldType');
        }

        return (
            <SingleSelectComponent disabled={!!disabled} onChange={this.handleChange} value={value}>
                {values.value.map(({name: value, title}) => {
                    if (typeof value !== 'string' && typeof value !== 'number') {
                        throw new Error('The children of "values" must only contain values of type string or number!');
                    }

                    return (
                        <SingleSelectComponent.Option key={value} value={value}>
                            {title}
                        </SingleSelectComponent.Option>
                    );
                })}
            </SingleSelectComponent>
        );
    }
}
