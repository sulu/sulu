// @flow
import React from 'react';
import MultiSelectComponent from '../../../components/MultiSelect';
import type {FieldTypeProps} from '../../../types';

const MISSING_VALUES_OPTIONS = 'The "values" option has to be set for the SingleSelect FieldType';

type Props = FieldTypeProps<Array<string | number>>;

export default class Select extends React.Component<Props> {
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
                {values.value.map(({value, title}) => {
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
