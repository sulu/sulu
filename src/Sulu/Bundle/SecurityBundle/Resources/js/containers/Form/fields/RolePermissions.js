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
        const {disabled, formInspector, value} = this.props;

        if (!formInspector.options.resourceKey) {
            throw new Error('The "resourceKey" must be available in order to load the available permissions!');
        }

        return (
            <RolePermissionsContainer
                disabled={disabled || undefined}
                onChange={this.handleChange}
                resourceKey={formInspector.options.resourceKey}
                value={value ? value : {}}
            />
        );
    }
}

export default RolePermissions;
