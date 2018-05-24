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
            fieldTypeOptions: {
                displayProperty,
                searchProperties,
                resourceKey,
            } = {},
            value,
        } = this.props;

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
