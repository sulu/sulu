// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Loader} from 'sulu-admin-bundle/components';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import securityContextStore from '../../stores/securityContextStore';
import type {Role} from '../../types';
import SystemRolePermissions from './SystemRolePermissions';
import type {RolePermissions as RolePermissionsType} from './types';

type Props = {|
    disabled: boolean,
    onChange: (value: RolePermissionsType) => void,
    resourceKey: string,
    system?: ?string,
    value: RolePermissionsType,
    webspaceKey?: ?string,
|};

@observer
class RolePermissions extends React.Component<Props> {
    static suluSecuritySystem: string;

    static defaultProps = {
        disabled: false,
    };

    @observable roles: ?Array<Role>;

    @action componentDidMount() {
        ResourceRequester.get('roles', {'include-anonymous': true}).then(action((response) => {
            this.roles = response._embedded.roles;
        }));
    }

    handleChange = (newSystemValue: RolePermissionsType, system: string) => {
        const {roles} = this;

        if (!roles) {
            return;
        }

        const {onChange, value} = this.props;
        const systemRoles = roles.filter((role) => role.system === system);
        onChange({
            ...Object.keys(value).reduce((values, roleId) => {
                if (systemRoles.some((systemRole) => systemRole.id.toString() == roleId)) {
                    return values;
                }

                values[roleId] = value[roleId];

                return values;
            }, {}),
            ...newSystemValue,
        });
    };

    render() {
        const {roles} = this;
        const {disabled, resourceKey, system, value, webspaceKey} = this.props;

        if (!roles) {
            return <Loader />;
        }

        const systems = system ? [RolePermissions.suluSecuritySystem, system] : securityContextStore.getSystems();

        return systems.reduce((systemMatrices, system) => {
            const actions = securityContextStore.getAvailableActions(resourceKey, system);
            const systemRoles = roles.filter((role) => role.system === system);

            if (systemRoles.length === 0 || actions.length === 0) {
                return systemMatrices;
            }

            const systemValues = Object.keys(value).reduce((systemValues, roleId) => {
                if (!systemRoles.some((systemRole) => systemRole.id.toString() == roleId)) {
                    return systemValues;
                }

                systemValues[roleId] = value[roleId];

                return systemValues;
            }, {});

            systemMatrices.push(
                <SystemRolePermissions
                    actions={actions}
                    disabled={disabled}
                    key={system}
                    onChange={this.handleChange}
                    resourceKey={resourceKey}
                    roles={systemRoles}
                    system={system}
                    values={systemValues}
                    webspaceKey={webspaceKey}
                />
            );

            return systemMatrices;
        }, []);
    }
}

export default RolePermissions;
