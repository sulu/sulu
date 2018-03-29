// @flow
import React from 'react';
import SingleSelectComponent from '../../../components/SingleSelect';
import type {FieldTypeProps} from '../../../types';

const MISSING_VALUES_OPTIONS = 'The "values" option has to be set for the SingleSelect FieldType';

export default class SingleSelect extends React.Component<FieldTypeProps<string | number>> {
    componentWillMount() {
        const {onChange, schemaOptions, value} = this.props;

        if (!schemaOptions) {
            return;
        }

        const {default_value: defaultValue} = schemaOptions;

        if (value === undefined) {
            onChange(defaultValue.value);
        }
    }

    handleChange = (value: string | number) => {
        const {onChange, onFinish} = this.props;

        onChange(value);

        if (onFinish) {
            onFinish();
        }
    };

    render() {
        const {schemaOptions, value} = this.props;
        if (!schemaOptions) {
            throw new Error(MISSING_VALUES_OPTIONS);
        }

        const {values} = schemaOptions;

        if (!values) {
            throw new Error(MISSING_VALUES_OPTIONS);
        }

        return (
            <SingleSelectComponent onChange={this.handleChange} value={value}>
                {values.value.map((value) => (
                    <SingleSelectComponent.Option key={value.value} value={value.value}>
                        {value.title}
                    </SingleSelectComponent.Option>
                ))}
            </SingleSelectComponent>
        );
    }
}
