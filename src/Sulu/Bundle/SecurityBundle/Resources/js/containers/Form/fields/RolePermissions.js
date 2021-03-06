// @flow
import React from 'react';
import {computed} from 'mobx';
import {webspaceStore} from 'sulu-page-bundle/stores';
import RolePermissionsContainer from '../../RolePermissions';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import type {RolePermissions as RolePermissionsType} from '../../RolePermissions/types';

class RolePermissions extends React.Component<FieldTypeProps<RolePermissionsType>> {
    @computed get webspace() {
        const {
            formInspector: {
                options: {
                    webspace,
                },
            },
        } = this.props;

        if (!webspace || !webspaceStore.hasWebspace(webspace)) {
            return undefined;
        }

        return webspaceStore.getWebspace(webspace);
    }

    @computed get webspaceKey() {
        const {
            webspace: {
                key,
            } = {},
        } = this;

        return key;
    }

    @computed get webspaceSecurity() {
        const {
            webspace: {
                security = {},
            } = {},
        } = this;

        return security;
    }

    @computed get permissionCheck() {
        const {permissionCheck} = this.webspaceSecurity;

        return permissionCheck;
    }

    @computed get system() {
        const {system} = this.webspaceSecurity;

        return system;
    }

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
                permissionCheck={this.permissionCheck}
                resourceKey={formInspector.options.resourceKey}
                system={this.system}
                value={value ? value : {}}
                webspaceKey={this.webspaceKey}
            />
        );
    }
}

export default RolePermissions;
