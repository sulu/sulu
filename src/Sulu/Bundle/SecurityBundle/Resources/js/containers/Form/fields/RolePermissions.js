// @flow
import React from 'react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import RolePermissionsContainer from '../../RolePermissions';
import type {RolePermissions as RolePermissionsType} from '../../RolePermissions/types';

class RolePermissions extends React.Component<FieldTypeProps<RolePermissionsType>> {
    handleChange = (value: RolePermissionsType) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, value} = this.props;

        return (
            <RolePermissionsContainer
                disabled={disabled || undefined}
                onChange={this.handleChange}
                value={value ? value : {}}
            />
        );
    }
}

export default RolePermissions;
