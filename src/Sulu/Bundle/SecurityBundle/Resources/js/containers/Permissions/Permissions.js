// @flow
import React, {Fragment} from 'react';
import {action, autorun, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Loader, MultiSelect} from 'sulu-admin-bundle/components';
import {webspaceStore} from 'sulu-page-bundle/stores';
import securityContextStore from '../../stores/securityContextStore';
import permissionsStyle from './permissions.scss';
import PermissionMatrix from './PermissionMatrix';
import type {SecurityContextGroups, SecurityContexts} from '../../stores/securityContextStore/types';
import type {ContextPermission} from './types';

type Props = {|
    disabled: boolean,
    onChange: (value: Array<ContextPermission>) => void,
    system: string,
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
            () => this.setSecurityContextGroups(securityContextStore.getSecurityContextGroups(this.system))
        );
    }

    @action setSecurityContextGroups(securityContextGroups: SecurityContextGroups) {
        this.securityContextGroups = securityContextGroups;
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
                    permissions,
                };
                newContextPermissions.push(newContextPermission);
            });
        }

        this.handleChange(newContextPermissions);
    };

    renderWebspaceMatrixes() {
        const {disabled, value} = this.props;
        if (!this.webspaceSecurityContextGroupKey) {
            return null;
        }

        return (
            <Fragment>
                <h2>{this.webspaceSecurityContextGroupKey}</h2>
                <div className={permissionsStyle.selectContainer}>
                    <MultiSelect
                        disabled={disabled}
                        onChange={this.handleWebspaceChange}
                        values={this.selectedWebspaces}
                    >
                        {webspaceStore.allWebspaces.map((webspace) => (
                            <MultiSelect.Option key={webspace.key} value={webspace.key}>
                                {webspace.name}
                            </MultiSelect.Option>
                        ))}
                    </MultiSelect>
                </div>
                <div className={permissionsStyle.matrixContainer}>
                    {this.selectedWebspaces.map((webspace, matrixIndex) => {
                        return (
                            <PermissionMatrix
                                contextPermissions={value}
                                disabled={disabled}
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
