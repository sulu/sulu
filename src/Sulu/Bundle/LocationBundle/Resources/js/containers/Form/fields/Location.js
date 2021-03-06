// @flow
import React from 'react';
import LocationComponent from '../../../containers/Location';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import type {Location as LocationValue} from '../../../types';

export default class Location extends React.Component<FieldTypeProps<?LocationValue>> {
    handleChange = (value: ?LocationValue) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {
            disabled,
            value,
        } = this.props;

        return (
            <LocationComponent
                disabled={!!disabled}
                onChange={this.handleChange}
                value={value}
            />
        );
    }
}
