// @flow
import React from 'react';
import MultiSelectComponent from '../../../components/MultiSelect';
import type {FieldTypeProps} from '../../../types';

const MISSING_VALUES_OPTIONS = 'The "values" option has to be set for the SingleSelect FieldType';

type Props = FieldTypeProps<Array<string | number>>;

export default class Select extends React.Component<Props> {
    constructor(props: FieldTypeProps<Array<string | number>>) {
        super(props);

        const {onChange, schemaOptions, value} = this.props;

        if (!schemaOptions) {
            return;
        }

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

    handleChange = (value: Array<string | number>) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {schemaOptions, disabled, value} = this.props;
        if (!schemaOptions) {
            throw new Error(MISSING_VALUES_OPTIONS);
        }

        const {values} = schemaOptions;

        if (!Array.isArray(values.value)) {
            throw new Error(MISSING_VALUES_OPTIONS);
        }

        return (
            <MultiSelectComponent disabled={!!disabled} onChange={this.handleChange} values={value || []}>
                {values.value.map(({name: value, title}) => {
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
