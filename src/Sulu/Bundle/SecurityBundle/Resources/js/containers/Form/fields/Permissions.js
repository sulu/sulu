// @flow
import React from 'react';
import {action, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {Button, Loader, Matrix} from 'sulu-admin-bundle/components';
import type {MatrixValues} from 'sulu-admin-bundle/components/Matrix/types';
import securityContextsStore from '../../../stores/SecurityContextsStore';
import type {Permission} from './types';
import WebspaceSelect from "sulu-content-bundle/components/WebspaceSelect/WebspaceSelect";

@observer
export default class Permissions extends React.Component<FieldTypeProps<?Array<Permission>>> {
    @observable securityContexts: Object;

    @computed get securityContext(): Object {
        const {formInspector} = this.props;
        const system = formInspector.getValueByPath('/system');

        return this.securityContexts[system];
    }

    @computed get availableWebspaces(): Array<string> {
        if (!this.props.value) {
            return [];
        }

        const webspaces = [];
        for (const value of this.props.value) {
            if (value.context.startsWith('sulu.webspaces.')) {
                const webspaceKey = value.context.replace('sulu.webspaces.', '');
                webspaces.push(webspaceKey);
            }
        }

        return webspaces;
    }

    @action componentDidMount() {
        securityContextsStore.loadSecurityContexts()
            .then(action((securityContexts) => {
                this.securityContexts = securityContexts;
            }));
    }

    handleChange = (matrixValues: MatrixValues) => {
        const {onChange, onFinish, value} = this.props;
        const newValue: Array<Permission> = value ? toJS(value) : [];

        Object.keys(matrixValues).map((matrixValuesKey) => {
            const matrixValue = matrixValues[matrixValuesKey];

            let valueSet = false;
            for (const newValueItem of newValue) {
                if (matrixValuesKey === newValueItem.context) {
                    newValueItem.permissions = matrixValue;

                    valueSet = true;
                    break;
                }
            }

            if (!valueSet) {
                const newValueItem: Permission = {
                    'id': undefined,
                    'context': matrixValuesKey,
                    'permissions': matrixValue,
                };
                newValue.push(newValueItem);
            }
        });

        onChange(newValue);
        onFinish();
    };

    handleAllButtonClick = () => {
        const {onChange, onFinish, value} = this.props;
        const newValue = value ? toJS(value) : [];

        for (const newValueItem of newValue) {
            Object.keys(newValueItem.permissions).map((permissionKey) => {
                newValueItem.permissions[permissionKey] = true;
            });
        }

        onChange(newValue);
        onFinish();
    };

    getIcon = (item: string) => {
        switch (item) {
            case 'view':
                return 'su-plus-circle';
            case 'add':
                return 'su-plus-circle';
            case 'edit':
                return 'su-plus-circle';
            case 'delete':
                return 'su-plus-circle';
            case 'security':
                return 'su-plus-circle';
            case 'live':
                return 'su-plus-circle';
            default:
                throw new Error('No icon defined for "' + item + '"');
        }
    };

    getMatrixValue = (searchedPermission: string) => {
        if (!this.props.value) {
            return;
        }

        for (const value of this.props.value) {
            if (searchedPermission === value.context) {
                return value.permissions;
            }
        }
    };

    getWebspacePermissions(webspace: string) {
        const webspacePermissions = this.securityContext['Webspaces'];
        const webspaceSettingsPermissions = this.securityContext['Webspace Settings'];

        const permissions = {};

        Object.keys(webspacePermissions).map((webspacePermissionKey) => {
            permissions[webspacePermissionKey.replace('#webspace#', webspace)]
                = toJS(webspacePermissions[webspacePermissionKey]);
        });

        Object.keys(webspaceSettingsPermissions).map((webspaceSettingsPermissionKey) => {
            permissions[webspaceSettingsPermissionKey.replace('#webspace#', webspace)]
                = toJS(webspaceSettingsPermissions[webspaceSettingsPermissionKey]);
        });

        return permissions;
    }

    renderWebspaceMatrix(webspace: string, matrixIndex: number) {
        const permissions = this.getWebspacePermissions(webspace);
        const matrixValues = {};
        const rows = [];

        Object.keys(permissions).map((permissionKey, rowIndex) => {
            const permissionItems = permissions[permissionKey];
            matrixValues[permissionKey] = this.getMatrixValue(permissionKey);

            rows.push(
                <Matrix.Row key={'row-' + rowIndex} name={permissionKey}>
                    {permissionItems.map((permissionItem, itemIndex) => (
                        <Matrix.Item
                            icon={this.getIcon(permissionItem)}
                            key={'item-' + itemIndex}
                            name={permissionItem}
                        />
                    ))}
                </Matrix.Row>
            );
        });

        return (
            <Matrix
                key={'matrix-webspace-' + matrixIndex}
                onChange={this.handleChange}
                title={webspace}
                values={matrixValues}
            >
                {rows}
            </Matrix>
        );
    }

    renderMatrix(permissionsKey: string, matrixIndex: number) {
        const permissions = this.securityContext[permissionsKey];
        const matrixValues = {};
        const rows = [];

        if (permissionsKey === 'Webspace Settings' || permissionsKey === 'Webspaces') {
            return null;
        }

        Object.keys(permissions).map((permissionKey, rowIndex) => {
            const permissionItems = permissions[permissionKey];
            matrixValues[permissionKey] = this.getMatrixValue(permissionKey);

            rows.push(
                <Matrix.Row key={'row-' + rowIndex} name={permissionKey}>
                    {permissionItems.map((permissionItem, itemIndex) => (
                        <Matrix.Item
                            icon={this.getIcon(permissionItem)}
                            key={'item-' + itemIndex}
                            name={permissionItem}
                        />
                    ))}
                </Matrix.Row>
            );
        });

        return (
            <Matrix
                key={'matrix-' + matrixIndex}
                onChange={this.handleChange}
                title={permissionsKey}
                values={matrixValues}
            >
                {rows}
            </Matrix>
        );
    }

    render() {
        if (!this.securityContexts) {
            return <Loader />;
        }

        return (
            <div>
                <Button active={true} onClick={this.handleAllButtonClick}>All</Button>
                {Object.keys(this.securityContext).map((permissionsKey, matrixIndex) => (
                    this.renderMatrix(permissionsKey, matrixIndex)
                ))}
                {this.availableWebspaces.map((webspace, matrixIndex) => (
                    this.renderWebspaceMatrix(webspace, matrixIndex)
                ))}
            </div>
        );
    }
}
