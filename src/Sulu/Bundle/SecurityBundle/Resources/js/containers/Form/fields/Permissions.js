// @flow
import React, {Fragment} from 'react';
import {action, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {Loader, Matrix} from 'sulu-admin-bundle/components';
import type {MatrixValues} from 'sulu-admin-bundle/components/Matrix/types';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import MultiSelect from 'sulu-admin-bundle/components/MultiSelect/MultiSelect';
import webspaceStore from 'sulu-content-bundle/stores/WebspaceStore';
import type {Webspace} from 'sulu-content-bundle/stores/WebspaceStore/types';
import securityContextsStore from '../../../stores/SecurityContextsStore';
import type {Actions, SecurityContextGroups, SecurityContexts} from '../../../stores/SecurityContextsStore/types';
import type {ContextPermission} from './types';
import permissionsStyle from './permissions.scss';

type Props = FieldTypeProps<?Array<ContextPermission>>;

@observer
export default class Permissions extends React.Component<Props> {
    static webspacePlaceholder = '#webspace#';

    @observable securityContextGroups: SecurityContextGroups = {};
    @observable availableWebspaces: Array<Webspace> = [];

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

    @computed get system(): string {
        const {formInspector} = this.props;
        const system = formInspector.getValueByPath('/system');

        if (!system || typeof system !== 'string') {
            throw new Error('Value "system" needs to be provided as string');
        }

        return system;
    }

    @computed get selectedWebspaces(): Array<string> {
        if (!this.props.value) {
            return [];
        }

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
        securityContextsStore.loadSecurityContextGroups(this.system).then(action((securityContextGroups) => {
            this.securityContextGroups = securityContextGroups;

            if (this.securityContextGroups.hasOwnProperty(this.webspaceSecurityContextGroupKey)) {
                webspaceStore.loadAllWebspaces().then(action((webspaces) => {
                    this.availableWebspaces = webspaces;
                }));
            }
        }));
    }

    handleMatrixChange = (matrixValues: MatrixValues) => {
        const {onChange, onFinish, value} = this.props;
        const contextPermissions: Array<ContextPermission> = value ? toJS(value) : [];

        Object.keys(matrixValues).map((matrixValuesKey) => {
            const matrixValue = matrixValues[matrixValuesKey];

            let valueSet = false;
            for (const contextPermission of contextPermissions) {
                if (matrixValuesKey === contextPermission.context) {
                    contextPermission.permissions = matrixValue;

                    valueSet = true;
                    break;
                }
            }

            if (!valueSet) {
                const newContextPermission: ContextPermission = {
                    'id': undefined,
                    'context': matrixValuesKey,
                    'permissions': matrixValue,
                };
                contextPermissions.push(newContextPermission);
            }
        });

        onChange(contextPermissions);
        onFinish();
    };

    getIcon = (action: string) => {
        switch (action) {
            case 'view':
                return 'su-eye';
            case 'add':
                return 'su-plus-circle';
            case 'edit':
                return 'su-pen';
            case 'delete':
                return 'su-trash-alt';
            case 'security':
                return 'su-lock';
            case 'live':
                return 'fa-signal';
            default:
                throw new Error('No icon defined for "' + action + '"');
        }
    };

    getMatrixValueFromPermission = (searchedPermission: string) => {
        const {value} = this.props;
        if (!value) {
            return;
        }

        for (const contextPermission of value) {
            if (searchedPermission === contextPermission.context) {
                return contextPermission.permissions;
            }
        }
    };

    getWebspaceSecurityContexts(webspace: string): SecurityContexts {
        if (!this.webspaceSecurityContextGroupKey) {
            return {};
        }

        const webspaceSecurityContextGroup = this.securityContextGroups[this.webspaceSecurityContextGroupKey];

        const securityContexts = {};

        Object.keys(webspaceSecurityContextGroup).sort().map((securityContextKey) => {
            securityContexts[securityContextKey.replace(Permissions.webspacePlaceholder, webspace)]
                = toJS(webspaceSecurityContextGroup[securityContextKey]);
        });

        return securityContexts;
    }

    renderWebspaceMatrix(webspace: string, matrixIndex: number) {
        const securityContexts = this.getWebspaceSecurityContexts(webspace);
        const matrixValues = {};
        const matrixRows = [];

        Object.keys(securityContexts).map((securityContextKey, rowIndex) => {
            const actions = securityContexts[securityContextKey];
            matrixValues[securityContextKey] = this.getMatrixValueFromPermission(securityContextKey);

            matrixRows.push(this.renderMatrixRow(rowIndex, securityContextKey, actions));
        });

        return (
            <Fragment key={'matrix-webspace-' + matrixIndex}>
                <h3>{webspace}</h3>
                <Matrix
                    onChange={this.handleMatrixChange}
                    values={matrixValues}
                >
                    {matrixRows}
                </Matrix>
            </Fragment>
        );
    }

    renderMatrix(securityContextGroupKey: string, matrixIndex: number) {
        if (securityContextGroupKey === 'Webspaces') {
            return null;
        }

        const securityContextGroup = this.securityContextGroups[securityContextGroupKey];
        const matrixValues = {};
        const matrixRows = [];

        Object.keys(securityContextGroup).map((securityContextKey, rowIndex) => {
            const actions = securityContextGroup[securityContextKey];
            matrixValues[securityContextKey] = this.getMatrixValueFromPermission(securityContextKey);

            matrixRows.push(this.renderMatrixRow(rowIndex, securityContextKey, actions));
        });

        return (
            <div className={permissionsStyle.matrixContainer} key={'matrix-' + matrixIndex}>
                <h2>{securityContextGroupKey}</h2>
                <Matrix
                    onChange={this.handleMatrixChange}
                    values={matrixValues}
                >
                    {matrixRows}
                </Matrix>
            </div>
        );
    }

    renderMatrixRow(rowIndex: number, securityContextKey: string, actions: Actions) {
        const secondPointPosition = securityContextKey.indexOf('.', securityContextKey.indexOf('.') + 1) + 1;
        const title = securityContextKey.substring(secondPointPosition);

        return (
            <Matrix.Row key={'row-' + rowIndex} name={securityContextKey} title={title}>
                {actions.map((action, itemIndex) => (
                    <Matrix.Item
                        icon={this.getIcon(action)}
                        key={'item-' + itemIndex}
                        name={action}
                        title={translate('sulu_security.' + action)}
                    />
                ))}
            </Matrix.Row>
        );
    }

    @action handleWebspaceSelectChange = (newSelectedWebspaces: Array<string | number>) => {
        const {onChange, onFinish, value} = this.props;
        const contextPermissions = value ? toJS(value) : [];
        const newContextPermissions = [];

        for (const contextPermission of contextPermissions) {
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

        onChange(newContextPermissions);
        onFinish();
    };

    renderWebspaceSelect() {
        if (!this.availableWebspaces || this.availableWebspaces.length < 1) {
            return <Loader />;
        }

        return (
            <div className={permissionsStyle.selectContainer}>
                <MultiSelect
                    allSelectedText="All selected"
                    noneSelectedText="None selected"
                    onChange={this.handleWebspaceSelectChange}
                    values={this.selectedWebspaces}
                >
                    {this.availableWebspaces.map((webspace, index) => (
                        <MultiSelect.Option key={index} value={webspace.key}>{webspace.name}</MultiSelect.Option>
                    ))}
                </MultiSelect>
            </div>
        );
    }

    renderWebspaceMatrixes() {
        if (!this.webspaceSecurityContextGroupKey) {
            return null;
        }

        return (
            <Fragment>
                <h2>Webspaces</h2>
                {this.renderWebspaceSelect()}
                <div className={permissionsStyle.matrixContainer}>
                    {this.selectedWebspaces.map((webspace, matrixIndex) => (
                        this.renderWebspaceMatrix(webspace.toString(), matrixIndex)
                    ))}
                </div>
            </Fragment>
        );
    }

    render() {
        if (!this.securityContextGroups) {
            return <Loader />;
        }

        return (
            <Fragment>
                {this.renderWebspaceMatrixes()}
                {Object.keys(this.securityContextGroups).sort().map((securityContextGroupKey, matrixIndex) => (
                    this.renderMatrix(securityContextGroupKey, matrixIndex)
                ))}
            </Fragment>
        );
    }
}
