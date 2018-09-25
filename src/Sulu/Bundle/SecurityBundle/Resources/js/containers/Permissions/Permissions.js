// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Loader} from 'sulu-admin-bundle/components';
import {MultiSelect} from 'sulu-admin-bundle/containers';
import securityContextsStore from '../../stores/SecurityContextsStore';
import type {SecurityContextGroups, SecurityContexts} from '../../stores/SecurityContextsStore/types';
import type {ContextPermission} from './types';
import permissionsStyle from './permissions.scss';
import PermissionMatrix from './PermissionMatrix';

type Props = {
    system: string,
    onChange: (value: Array<ContextPermission>) => void,
    value: Array<ContextPermission>,
};

@observer
export default class Permissions extends React.Component<Props> {
    static webspacePlaceholder = '#webspace#';

    @observable securityContextGroups: SecurityContextGroups;

    @computed get webspaceContextPermissionPrefix(): string {
        if (this.webspaceSecurityContextGroupKey) {
            const securityContextGroup = this.securityContextGroups[this.webspaceSecurityContextGroupKey];
            for (const securityContextKey of Object.keys(securityContextGroup)) {
                if (securityContextKey.indexOf(Permissions.webspacePlaceholder) !== -1) {
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
                if (securityContextKey.indexOf(Permissions.webspacePlaceholder) !== -1) {
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

                if (webspaceKey.indexOf('.') !== -1) {
                    continue;
                }

                selectedWebspaces.push(webspaceKey);
            }
        }

        return selectedWebspaces.sort();
    }

    @action componentDidMount() {
        securityContextsStore.loadSecurityContextGroups(this.props.system).then(action((securityContextGroups) => {
            this.securityContextGroups = securityContextGroups;
        }));
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
                const webspaceKey = suffix.indexOf('.') === -1 ? suffix : suffix.substring(0, suffix.indexOf('.'));

                if (newSelectedWebspaces.indexOf(webspaceKey) === -1) {
                    continue;
                }
            }

            newContextPermissions.push(contextPermission);
        }

        const webspacesToAdd = newSelectedWebspaces.filter((newSelectedWebspace) => {
            return this.selectedWebspaces.indexOf(newSelectedWebspace) < 0;
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

        return (
            <Fragment>
                <h2>{this.webspaceSecurityContextGroupKey}</h2>
                <div className={permissionsStyle.selectContainer}>
                    <MultiSelect
                        apiOptions={{'checkForPermissions': 0}}
                        displayProperty={'name'}
                        idProperty={'key'}
                        onChange={this.handleWebspaceChange}
                        resourceKey={'webspaces'}
                        values={this.selectedWebspaces}
                    />
                </div>
                <div className={permissionsStyle.matrixContainer}>
                    {this.selectedWebspaces.map((webspace, matrixIndex) => {
                        return (
                            <PermissionMatrix
                                contextPermissions={this.props.value}
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

    render() {
        if (!this.securityContextGroups) {
            return <Loader />;
        }

        const {value} = this.props;
        return (
            <Fragment>
                {this.renderWebspaceMatrixes()}
                {Object.keys(this.securityContextGroups).sort().map((securityContextGroupKey, matrixIndex) => {
                    // ignore webspace group here
                    if (this.webspaceSecurityContextGroupKey
                        && this.webspaceSecurityContextGroupKey === securityContextGroupKey
                    ) {
                        return null;
                    }

                    return (
                        <PermissionMatrix
                            contextPermissions={value}
                            key={matrixIndex}
                            onChange={this.handleChange}
                            securityContexts={this.securityContextGroups[securityContextGroupKey]}
                            title={securityContextGroupKey}
                        />
                    );
                })}
            </Fragment>
        );
    }
}
