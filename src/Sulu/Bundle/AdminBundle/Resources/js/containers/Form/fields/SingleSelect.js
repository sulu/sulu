// @flow
import React from 'react';
import SingleSelectComponent from '../../../components/SingleSelect';
import type {FieldTypeProps} from '../../../types';

const MISSING_VALUES_OPTIONS = 'The "values" option has to be set for the SingleSelect FieldType';

export default class SingleSelect extends React.Component<FieldTypeProps<string | number>> {
    render() {
        const {onChange, options, value} = this.props;
        if (!options) {
            throw new Error(MISSING_VALUES_OPTIONS);
        }

        const {default_value: defaultValue, values} = options;

        if (!values) {
            throw new Error(MISSING_VALUES_OPTIONS);
        }

        return (
            <SingleSelectComponent onChange={onChange} value={value || defaultValue}>
                {Object.keys(values).map((key) => (
                    <SingleSelectComponent.Option key={key} value={key}>{values[key]}</SingleSelectComponent.Option>
                ))}
            </SingleSelectComponent>
        );
    }
}
