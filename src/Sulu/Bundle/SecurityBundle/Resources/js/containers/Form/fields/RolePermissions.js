// @flow
import React from 'react';
import {computed} from 'mobx';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {webspaceStore} from 'sulu-page-bundle/stores';
import RolePermissionsContainer from '../../RolePermissions';
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

    @computed get system() {
        const {
            webspace: {
                security: {
                    system,
                } = {},
            } = {},
        } = this;

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
                resourceKey={formInspector.options.resourceKey}
                system={this.system}
                value={value ? value : {}}
                webspaceKey={this.webspaceKey}
            />
        );
    }
}

export default RolePermissions;
