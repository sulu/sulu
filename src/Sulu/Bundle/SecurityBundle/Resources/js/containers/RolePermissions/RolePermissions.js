// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Loader, Matrix} from 'sulu-admin-bundle/components';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import securityContextStore from '../../stores/SecurityContextStore';
import {getActionIcon} from '../../utils/Permission';
import type {Role} from '../../types';
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

    @observable roles: ?Array<Role>;
    @observable actions: ?Array<string>;

    componentDidMount() {
        const {resourceKey} = this.props;

        securityContextStore.loadAvailableActions(resourceKey).then(action((actions) => {
            this.actions = actions.filter((action) => action !== 'security');
        }));

        ResourceRequester.get('roles').then(action((response) => {
            this.roles = response._embedded.roles;
        }));
    }

    @computed get defaultValue() {
        const {actions, roles} = this;

        if (!actions || ! roles) {
            return {};
        }

        return roles.reduce((value, role) => {
            value[role.id] = actions.reduce((actionValue, action) => {
                actionValue[action] = true;

                return actionValue;
            }, {});

            return value;
        }, {});
    }

    handleChange = (value: RolePermissionsType) => {
        const {onChange} = this.props;
        onChange(value);
    };

    render() {
        const {actions, roles} = this;
        const {disabled, value} = this.props;

        if (!roles || !actions) {
            return <Loader />;
        }

        return (
            <Matrix
                disabled={disabled}
                onChange={this.handleChange}
                values={Object.keys(value).length > 0 ? value : this.defaultValue}
            >
                {roles.map((role) => (
                    <Matrix.Row key={role.id} name={role.id.toString()} title={role.name}>
                        {actions.map((action) => (
                            <Matrix.Item icon={getActionIcon(action)} key={action} name={action} />
                        ))}
                    </Matrix.Row>
                ))}
            </Matrix>
        );
    }
}

export default RolePermissions;
