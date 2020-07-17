// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
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
    value: RolePermissionsType,
|};

@observer
class RolePermissions extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    // TODO Could be removed by using resourceKey for security as well instead of separate security context
    static resourceKeyMapping: {[resourceKey: string]: string};

    @observable roles: ?Array<Role>;

    @action componentDidMount() {
        ResourceRequester.get('roles').then(action((response) => {
            this.roles = response._embedded.roles;
        }));
    }

    @computed get defaultValue() {
        const {resourceKey} = this.props;
        const {roles} = this;

        if (!roles) {
            return {};
        }

        const securityContext = RolePermissions.resourceKeyMapping[resourceKey];

        return roles.reduce((value, role) => {
            const rolePermission = role.permissions.find((permission) => permission.context === securityContext);
            value[role.id] = securityContextStore.getAvailableActions(resourceKey, role.system)
                .reduce((actionValue, action) => {
                    actionValue[action] = rolePermission ? rolePermission.permissions[action] : false;

                    return actionValue;
                }, {});

            return value;
        }, {});
    }

    handleChange = (newValue: RolePermissionsType) => {
        const {onChange, value} = this.props;
        onChange({...value, ...newValue});
    };

    render() {
        const {roles} = this;
        const {disabled, resourceKey, value} = this.props;

        if (!roles) {
            return <Loader />;
        }

        const values = Object.keys(value).length > 0 ? value : this.defaultValue;

        return securityContextStore.getSystems().reduce((systemMatrices, system) => {
            const actions = securityContextStore.getAvailableActions(resourceKey, system);
            const systemRoles = roles.filter((role) => role.system === system);

            if (systemRoles.length === 0) {
                return systemMatrices;
            }

            const systemValues = Object.keys(values).reduce((systemValues, roleId) => {
                if (!systemRoles.some((systemRole) => systemRole.id.toString() == roleId)) {
                    return systemValues;
                }

                systemValues[roleId] = values[roleId];

                return systemValues;
            }, {});

            systemMatrices.push(
                <SystemRolePermissions
                    actions={actions}
                    disabled={disabled}
                    key={system}
                    onChange={this.handleChange}
                    roles={systemRoles}
                    system={system}
                    values={systemValues}
                />
            );

            return systemMatrices;
        }, []);
    }
}

export default RolePermissions;
