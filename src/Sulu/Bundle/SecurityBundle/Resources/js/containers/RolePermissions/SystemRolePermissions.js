// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Heading, Matrix, Toggler} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import securityContextStore from '../../stores/securityContextStore';
import {getActionIcon} from '../../utils/Permission';
import type {Role} from '../../types';
import systemRolePermissionsStyles from './systemRolePermissions.scss';
import type {RolePermissions} from './types';

type Props = {|
    actions: Array<string>,
    disabled: boolean,
    onChange: (value: RolePermissions, system: string) => void,
    resourceKey: string,
    roles: Array<Role>,
    system: string,
    values: RolePermissions,
|};

@observer
class SystemRolePermissions extends React.Component<Props> {
    @observable active: boolean = false;

    @action componentDidMount() {
        this.active = this.hasValues;
    }

    handleChange = (values: RolePermissions) => {
        const {onChange, system} = this.props;
        onChange(values, system);
    };

    @action handleActiveChange = (active: boolean) => {
        this.active = active;

        if (!this.active) {
            const {onChange, system} = this.props;
            onChange({}, system);
        }
    };

    @computed get defaultValue() {
        const {resourceKey, roles} = this.props;

        if (!roles) {
            return {};
        }

        const securityContext = securityContextStore.getSecurityContextByResourceKey(resourceKey);

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

    @computed get hasValues() {
        const {values} = this.props;

        return Object.keys(values).length > 0;
    }

    render() {
        const {actions, disabled, roles, system, values} = this.props;

        return (
            <div className={systemRolePermissionsStyles.systemRolePermissions}>
                <Heading label={translate('sulu_security.system_permission_heading', {system})}>
                    <Toggler checked={this.active} onChange={this.handleActiveChange} />
                </Heading>
                {this.active &&
                    <Matrix
                        className={systemRolePermissionsStyles.matrix}
                        disabled={disabled}
                        onChange={this.handleChange}
                        values={this.hasValues ? values : this.defaultValue}
                    >
                        {roles.map((role) => (
                            <Matrix.Row key={role.id} name={role.id.toString()} title={role.name}>
                                {actions.map((action) => (
                                    <Matrix.Item icon={getActionIcon(action)} key={action} name={action} />
                                ))}
                            </Matrix.Row>
                        ))}
                    </Matrix>
                }
            </div>
        );
    }
}

export default SystemRolePermissions;
