// @flow
import React from 'react';
import {toJS} from 'mobx';
import {observer} from 'mobx-react';
import Matrix from 'sulu-admin-bundle/components/Matrix';
import {translate} from 'sulu-admin-bundle/utils';
import type {MatrixValues} from 'sulu-admin-bundle/components/Matrix/types';
import type {Actions, SecurityContexts} from '../../stores/SecurityContextsStore/types';
import type {ContextPermission} from './types';
import permissionsStyle from './permissions.scss';

type Props = {|
    contextPermissions: Array<ContextPermission>,
    onChange: (value: Array<ContextPermission>) => void,
    securityContexts: SecurityContexts,
    subTitle?: string,
    title?: string,
|};

@observer
export default class PermissionMatrix extends React.Component<Props> {
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

    getMatrixValueFromContextPermission = (searchedKey: string) => {
        const {contextPermissions} = this.props;
        if (!contextPermissions) {
            return;
        }

        for (const contextPermission of contextPermissions) {
            if (searchedKey === contextPermission.context) {
                return contextPermission.permissions;
            }
        }
    };

    handleMatrixChange = (matrixValues: MatrixValues) => {
        const {onChange, contextPermissions} = this.props;
        const newContextPermissions = toJS(contextPermissions);

        Object.keys(matrixValues).map((matrixValuesKey) => {
            const matrixValue = matrixValues[matrixValuesKey];

            let valueSet = false;
            for (const contextPermission of newContextPermissions) {
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
                newContextPermissions.push(newContextPermission);
            }
        });

        onChange(newContextPermissions);
    };

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

    render() {
        const {title, subTitle, securityContexts} = this.props;
        const matrixValues = {};
        const matrixRows = [];

        Object.keys(securityContexts).map((securityContextKey, rowIndex) => {
            const actions = securityContexts[securityContextKey];
            matrixValues[securityContextKey] = this.getMatrixValueFromContextPermission(securityContextKey);

            matrixRows.push(this.renderMatrixRow(rowIndex, securityContextKey, actions));
        });

        return (
            <div className={permissionsStyle.matrixContainer}>
                {title &&
                    <h2>{title}</h2>
                }
                {subTitle &&
                    <h3>{subTitle}</h3>
                }
                <Matrix
                    onChange={this.handleMatrixChange}
                    values={matrixValues}
                >
                    {matrixRows}
                </Matrix>
            </div>
        );
    }
}
