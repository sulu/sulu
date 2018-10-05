// @flow
import React, {Fragment} from 'react';
import {action, autorun, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Loader} from 'sulu-admin-bundle/components';
import {MultiSelect} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import type {ContextPermission} from "../Permissions";
import RoleAssignment from './RoleAssignment';
import roleAssignmentsStyle from './roleAssignments.scss';

type Props = {
    onChange: (value: Array<Object>) => void,
    value: Array<Object>,
};

@observer
export default class RoleAssignments extends React.Component<Props> {
    @computed get selectedRoles(): Array<string> {
        const selectedRoles = [];
        for (const role of this.props.value) {
            selectedRoles.push(role.id);
        }

        return selectedRoles.sort();
    }

    handleRoleChange = (newRoleIds: Array<string>, newRoles?: Array<Object> = []) => {
        const newValue = [];

        for (const currentUserRole of this.props.value) {
            if (!newRoleIds.includes(currentUserRole.role.id)) {
                continue;
            }

            newValue.push(currentUserRole);
        }

        const rolesToAdd = newRoles.filter((newSelectedRole) => {
            return !this.selectedRoles.includes(newSelectedRole.id);
        });
        for (const role of rolesToAdd) {
            newValue.push({
                locales: [],
                role: role,
            })
        }

        this.props.onChange(newValue);
    };

    handleRoleAssignmentChange = (newRoleAssignment: Object) => {
        const newValue = [];

        for (const currentUserRole of this.props.value) {
            if (currentUserRole.role.id === newRoleAssignment.role.id) {
                newValue.push(newRoleAssignment);

                continue;
            }

            newValue.push(currentUserRole);
        }

        this.props.onChange(newValue);
    };

    render() {
        const {value} = this.props;

        console.log(value);

        return (
            <Fragment>
                <div className={roleAssignmentsStyle.selectContainer}>
                    <MultiSelect
                        displayProperty={'name'}
                        onChange={this.handleRoleChange}
                        resourceKey={'roles'}
                        values={this.selectedRoles}
                    />
                </div>
                <div className={roleAssignmentsStyle.roleAssignmentsTitle}>
                    {translate('sulu_security.role_locales_selection')}
                </div>
                <div className={roleAssignmentsStyle.roleAssignmentsContainer}>
                    {value.map((userRole, key) => {
                        return (
                            <RoleAssignment key={key} onChange={this.handleRoleAssignmentChange} value={userRole} />
                        );
                    })}
                </div>
            </Fragment>
        );
    }
}
