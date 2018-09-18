// @flow
import React from 'react';
import {action, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {Button, Loader, Matrix} from 'sulu-admin-bundle/components';
import type {MatrixValues} from 'sulu-admin-bundle/components/Matrix/types';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import MultiSelect from 'sulu-admin-bundle/components/MultiSelect/MultiSelect';
import webspaceStore from 'sulu-content-bundle/stores/WebspaceStore';
import securityContextsStore from '../../../stores/SecurityContextsStore';
import type {ContextPermission} from './types';
import type {Webspace} from 'sulu-content-bundle/stores/WebspaceStore/types';
import permissionsStyle from './permissions.scss';
import type {Actions, SecurityContextGroups, SecurityContexts} from '../../../stores/SecurityContextsStore/types';

type Props = FieldTypeProps<?Array<ContextPermission>>;

@observer
export default class Permissions extends React.Component<Props> {
    @observable securityContextGroups: SecurityContextGroups;
    @observable availableWebspaces: Array<Webspace> = [];

    @computed get selectedWebspaces() {
        if (!this.props.value) {
            return [];
        }

        const selectedWebspaces = [];
        for (const contextPermission of this.props.value) {
            if (contextPermission.context.startsWith('sulu.webspaces.')) {
                const webspaceKey = contextPermission.context.replace('sulu.webspaces.', '');
                selectedWebspaces.push(webspaceKey);
            }
        }

        return selectedWebspaces.sort();
    }

    @action componentDidMount() {
        const {formInspector} = this.props;
        const system = formInspector.getValueByPath('/system');

        securityContextsStore.loadSecurityContextGroups(system).then(action((securityContextGroups) => {
            this.securityContextGroups = securityContextGroups;
        }));
        webspaceStore.loadAllWebspaces().then(action((webspaces) => {
            this.availableWebspaces = webspaces;
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

    handleAllButtonClick = () => {
        const {onChange, onFinish, value} = this.props;
        const contextPermissions = value ? toJS(value) : [];

        for (const contextPermission of contextPermissions) {
            Object.keys(contextPermission.permissions).map((permissionKey) => {
                contextPermission.permissions[permissionKey] = true;
            });
        }

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
        const webspaceSecurityContextGroup = this.securityContextGroups['Webspaces'];
        const webspaceSettingsSecurityContextGroup = this.securityContextGroups['Webspace Settings'];

        const securityContexts = {};

        Object.keys(webspaceSecurityContextGroup).map((securityContextKey) => {
            securityContexts[securityContextKey.replace('#webspace#', webspace)]
                = toJS(webspaceSecurityContextGroup[securityContextKey]);
        });

        Object.keys(webspaceSettingsSecurityContextGroup).map((securityContextKey) => {
            securityContexts[securityContextKey.replace('#webspace#', webspace)]
                = toJS(webspaceSettingsSecurityContextGroup[securityContextKey]);
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
            <Matrix
                key={'matrix-webspace-' + matrixIndex}
                onChange={this.handleMatrixChange}
                title={'Webspace: "' + webspace + '"'}
                values={matrixValues}
            >
                {matrixRows}
            </Matrix>
        );
    }

    renderMatrix(securityContextGroupKey: string, matrixIndex: number) {
        if (securityContextGroupKey === 'Webspace Settings' || securityContextGroupKey === 'Webspaces') {
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
            <Matrix
                key={'matrix-' + matrixIndex}
                onChange={this.handleMatrixChange}
                title={securityContextGroupKey}
                values={matrixValues}
            >
                {matrixRows}
            </Matrix>
        );
    }

    renderMatrixRow(rowIndex: number, securityContextKey: string, actions: Actions) {
        return (
            <Matrix.Row key={'row-' + rowIndex} name={securityContextKey}>
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
            if (contextPermission.context.startsWith('sulu.webspaces.')) {
                const webspaceKey = contextPermission.context.replace('sulu.webspaces.', '');
                if (newSelectedWebspaces.indexOf(webspaceKey) === -1) {
                    continue;
                }
            } else if (contextPermission.context.startsWith('sulu.webspace_settings.')) {
                const suffix = contextPermission.context.replace('sulu.webspace_settings.', '');
                const webspaceKey = suffix.substring(suffix.indexOf('.'));
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
        );
    }

    render() {
        if (!this.securityContextGroups) {
            return <Loader />;
        }

        return (
            <div className={permissionsStyle.permissions}>
                <Button active={true} onClick={this.handleAllButtonClick}>All</Button>
                {this.renderWebspaceSelect()}
                {this.selectedWebspaces.map((webspace, matrixIndex) => (
                    this.renderWebspaceMatrix(webspace.toString(), matrixIndex)
                ))}
                {Object.keys(this.securityContextGroups).map((securityContextGroupKey, matrixIndex) => (
                    this.renderMatrix(securityContextGroupKey, matrixIndex)
                ))}
            </div>
        );
    }
}
