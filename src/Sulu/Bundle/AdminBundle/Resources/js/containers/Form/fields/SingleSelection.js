// @flow
import React from 'react';
import type {FieldTypeProps} from '../../../types';
import AutoComplete from '../../../containers/AutoComplete';

export default class SingleSelection extends React.Component<FieldTypeProps<?Object>>
{
    handleChange = (value: ?Object) => {
        const {onChange, onFinish} = this.props;

        onChange(value);

        if (onFinish) {
            onFinish();
        }
    };

    render() {
        const {
            fieldTypeOptions,
            value,
        } = this.props;

        if (!fieldTypeOptions || !fieldTypeOptions.auto_complete) {
            throw new Error('The single_selection field needs an "auto_complete" option if rendered as AutoComplete');
        }

        const {
            auto_complete: {
                displayProperty,
                searchProperties,
                resourceKey,
            },
        } = fieldTypeOptions;

        return (
            <AutoComplete
                displayProperty={displayProperty}
                searchProperties={searchProperties}
                onChange={this.handleChange}
                resourceKey={resourceKey}
                value={value}
            />
        );
    }
}
