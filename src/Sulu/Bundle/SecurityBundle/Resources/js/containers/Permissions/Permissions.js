// @flow
import React, {Fragment} from 'react';
import {action, autorun, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Loader} from 'sulu-admin-bundle/components';
import {ResourceMultiSelect} from 'sulu-admin-bundle/containers';
import {userStore} from 'sulu-admin-bundle/stores';
import securityContextStore from '../../stores/SecurityContextStore';
import type {SecurityContextGroups, SecurityContexts} from '../../stores/SecurityContextStore/types';
import type {ContextPermission} from './types';
import permissionsStyle from './permissions.scss';
import PermissionMatrix from './PermissionMatrix';

type Props = {|
    disabled: boolean,
    system: string,
    onChange: (value: Array<ContextPermission>) => void,
    value: Array<ContextPermission>,
|};

@observer
class Permissions extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    static webspacePlaceholder = '#webspace#';

    systemDisposer: () => void;

    @observable securityContextGroups: SecurityContextGroups;

    @action componentDidMount() {
        this.systemDisposer = autorun(
            () => {
                securityContextStore.loadSecurityContextGroups(this.system).then(action((securityContextGroups) => {
                    this.securityContextGroups = securityContextGroups;
                }));
            }
        );
    }

    componentWillUnmount() {
        this.systemDisposer();
    }

    @computed get system(): string {
        return this.props.system;
    }

    @computed get webspaceContextPermissionPrefix(): string {
        if (this.webspaceSecurityContextGroupKey) {
            const securityContextGroup = this.securityContextGroups[this.webspaceSecurityContextGroupKey];
            for (const securityContextKey of Object.keys(securityContextGroup)) {
                if (securityContextKey.includes(Permissions.webspacePlaceholder)) {
                    return securityContextKey.substring(0, securityContextKey.indexOf('#'));
                }
            }
        }

        throw new Error('Webspace context permission prefix not found');
    }

    @computed get webspaceSecurityContextGroupKey(): ?string {
        for (const securityContextGroupKey of Object.keys(this.securityContextGroups)) {
            const securityContextGroup = this.securityContextGroups[securityContextGroupKey];
            for (const securityContextKey of Object.keys(securityContextGroup)) {
                if (securityContextKey.includes(Permissions.webspacePlaceholder)) {
                    return securityContextGroupKey;
                }
            }
        }

        return null;
    }

    @computed get selectedWebspaces(): Array<string> {
        const selectedWebspaces = [];
        for (const contextPermission of this.props.value) {
            if (contextPermission.context.startsWith(this.webspaceContextPermissionPrefix)) {
                const webspaceKey = contextPermission.context.replace(this.webspaceContextPermissionPrefix, '');

                if (webspaceKey.includes('.')) {
                    continue;
                }

                selectedWebspaces.push(webspaceKey);
            }
        }

        return selectedWebspaces.sort();
    }

    handleChange = (value: Array<ContextPermission>) => {
        const {onChange} = this.props;

        onChange(value);
    };

    getWebspaceSecurityContexts(webspace: string): SecurityContexts {
        if (!this.webspaceSecurityContextGroupKey) {
            return {};
        }

        const webspaceSecurityContextGroup = this.securityContextGroups[this.webspaceSecurityContextGroupKey];

        const securityContexts = {};

        Object.keys(webspaceSecurityContextGroup).sort().map((securityContextKey) => {
            securityContexts[securityContextKey.replace(Permissions.webspacePlaceholder, webspace)]
                = webspaceSecurityContextGroup[securityContextKey];
        });

        return securityContexts;
    }

    @action handleWebspaceChange = (newSelectedWebspaces: Array<string>) => {
        const newContextPermissions = [];
        for (const contextPermission of this.props.value) {
            if (contextPermission.context.startsWith(this.webspaceContextPermissionPrefix)) {
                const suffix = contextPermission.context.replace(this.webspaceContextPermissionPrefix, '');
                const webspaceKey = !suffix.includes('.') ? suffix : suffix.substring(0, suffix.indexOf('.'));

                if (!newSelectedWebspaces.includes(webspaceKey)) {
                    continue;
                }
            }

            newContextPermissions.push(contextPermission);
        }

        const webspacesToAdd = newSelectedWebspaces.filter((newSelectedWebspace) => {
            return !this.selectedWebspaces.includes(newSelectedWebspace);
        });
        for (const webspaceToAdd of webspacesToAdd) {
            const securityContexts = this.getWebspaceSecurityContexts(webspaceToAdd.toString());

            Object.keys(securityContexts).map((securityContextKey) => {
                const permissions = {};
                const actions = securityContexts[securityContextKey];

                for (const action of actions) {
                    permissions[action] = false;
                }

                const newContextPermission: ContextPermission = {
                    'id': undefined,
                    'context': securityContextKey,
                    'permissions': permissions,
                };
                newContextPermissions.push(newContextPermission);
            });
        }

        this.handleChange(newContextPermissions);
    };

    renderWebspaceMatrixes() {
        if (!this.webspaceSecurityContextGroupKey) {
            return null;
        }

        if (!userStore.user) {
            throw new Error('This component needs a logged in user to determine the locale!');
        }

        return (
            <Fragment>
                <h2>{this.webspaceSecurityContextGroupKey}</h2>
                <div className={permissionsStyle.selectContainer}>
                    <ResourceMultiSelect
                        apiOptions={{checkForPermissions: 0, locale: userStore.user.locale}}
                        disabled={this.props.disabled}
                        displayProperty="name"
                        idProperty="key"
                        onChange={this.handleWebspaceChange}
                        resourceKey="webspaces"
                        values={this.selectedWebspaces}
                    />
                </div>
                <div className={permissionsStyle.matrixContainer}>
                    {this.selectedWebspaces.map((webspace, matrixIndex) => {
                        return (
                            <PermissionMatrix
                                contextPermissions={this.props.value}
                                disabled={this.props.disabled}
                                key={matrixIndex}
                                onChange={this.handleChange}
                                securityContexts={this.getWebspaceSecurityContexts(webspace)}
                                subTitle={webspace}
                            />
                        );
                    })}
                </div>
            </Fragment>
        );
    }

    renderMatrixes(): Array<*> {
        const {disabled, value} = this.props;

        return Object.keys(this.securityContextGroups).sort().map((securityContextGroupKey, matrixIndex) => {
            // ignore webspace group here
            if (this.webspaceSecurityContextGroupKey
                && this.webspaceSecurityContextGroupKey === securityContextGroupKey
            ) {
                return null;
            }

            const securityContexts = this.securityContextGroups[securityContextGroupKey];

            return (
                <PermissionMatrix
                    contextPermissions={value}
                    disabled={disabled}
                    key={matrixIndex}
                    onChange={this.handleChange}
                    securityContexts={securityContexts}
                    title={securityContextGroupKey}
                />
            );
        });
    }

    render() {
        if (!this.securityContextGroups) {
            return <Loader />;
        }

        return (
            <Fragment>
                {this.renderWebspaceMatrixes()}
                {this.renderMatrixes()}
            </Fragment>
        );
    }
}

export default Permissions;
