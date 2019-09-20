// @flow
import React from 'react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import type {Location as LocationValue} from '../../../types';
import LocationComponent from '../../../containers/Location';

export default class Location extends React.Component<FieldTypeProps<?LocationValue>> {
    handleChange = (value: ?LocationValue) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {
            disabled,
            fieldTypeOptions,
            value,
        } = this.props;
        const {provider, providerOptions} = fieldTypeOptions;

        return (
            <LocationComponent
                disabled={!!disabled}
                onChange={this.handleChange}
                provider={provider}
                providerOptions={providerOptions}
                value={value}
            />
        );
    }
}
