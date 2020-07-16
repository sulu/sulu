// @flow
import React from 'react';
import {Matrix} from 'sulu-admin-bundle/components';
import {getActionIcon} from '../../utils/Permission';
import type {Role} from '../../types';
import systemRolePermissionsStyles from './systemRolePermissions.scss';
import type {RolePermissions} from './types';

type Props = {|
    actions: Array<string>,
    disabled: boolean,
    onChange: (value: RolePermissions) => void,
    roles: Array<Role>,
    values: RolePermissions,
|};

export default class SystemRolePermissions extends React.Component<Props> {
    handleChange = (values: RolePermissions) => {
        const {onChange} = this.props;
        onChange(values);
    };

    render() {
        const {actions, disabled, roles, values} = this.props;

        return (
            <div className={systemRolePermissionsStyles.matrix}>
                <Matrix
                    disabled={disabled}
                    onChange={this.handleChange}
                    values={values}
                >
                    {roles.map((role) => (
                        <Matrix.Row key={role.id} name={role.id.toString()} title={role.name}>
                            {actions.map((action) => (
                                <Matrix.Item icon={getActionIcon(action)} key={action} name={action} />
                            ))}
                        </Matrix.Row>
                    ))}
                </Matrix>
            </div>
        );
    }
}
