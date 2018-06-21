// @flow
import React from 'react';
import SingleSelectComponent from '../../../components/SingleSelect';
import type {FieldTypeProps} from '../../../types';

const MISSING_VALUES_OPTIONS = 'The "values" option has to be set for the SingleSelect FieldType';

export default class SingleSelect extends React.Component<FieldTypeProps<string | number>> {
    constructor(props: FieldTypeProps<string | number>) {
        super(props);

        const {onChange, schemaOptions, value} = this.props;

        if (!schemaOptions) {
            return;
        }

        const {
            default_value: {
                value: defaultValue,
            } = {},
        } = schemaOptions;

        if (!defaultValue) {
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
        const {schemaOptions, value} = this.props;
        if (!schemaOptions) {
            throw new Error(MISSING_VALUES_OPTIONS);
        }

        const {values} = schemaOptions;

        if (!Array.isArray(values.value)) {
            throw new Error(MISSING_VALUES_OPTIONS);
        }

        return (
            <SingleSelectComponent onChange={this.handleChange} value={value}>
                {values.value.map(({value, title}) => {
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
