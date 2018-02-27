// @flow
import React from 'react';
import SingleSelectComponent from '../../../components/SingleSelect';
import type {FieldTypeProps} from '../../../types';

const MISSING_VALUES_OPTIONS = 'The "values" option has to be set for the SingleSelect FieldType';

export default class SingleSelect extends React.Component<FieldTypeProps<string | number>> {
    componentWillMount() {
        const {onChange, options, value} = this.props;

        if (!options) {
            return;
        }

        const {default_value: defaultValue} = options;

        if (value === undefined) {
            onChange(defaultValue);
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
        const {options, value} = this.props;
        if (!options) {
            throw new Error(MISSING_VALUES_OPTIONS);
        }

        const {values} = options;

        if (!values) {
            throw new Error(MISSING_VALUES_OPTIONS);
        }

        return (
            <SingleSelectComponent onChange={this.handleChange} value={value}>
                {values.map((value) => (
                    <SingleSelectComponent.Option key={value.value} value={value.value}>
                        {value.name}
                    </SingleSelectComponent.Option>
                ))}
            </SingleSelectComponent>
        );
    }
}
