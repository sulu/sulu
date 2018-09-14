// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import Matrix from 'sulu-admin-bundle/components/Matrix';

@observer
export default class Permissions extends React.Component<FieldTypeProps<typeof undefined>> {
    @computed get availablePermissions(): Object {
        const {formInspector, schemaOptions} = this.props;
        const system = formInspector.getValueByPath('/system');

        return schemaOptions.data.value[system];
    }

    handleChange = () => {

    };

    renderRow() {
        this.toolbarActions.forEach((toolbarAction) => {
            toolbarAction.setLocales(this.locales);
        });
    }

    render() {
        return (
            <div>
                {Object.keys(this.availablePermissions).map((permissionKey, matrixIndex) => {
                    const permission = this.availablePermissions[permissionKey];

                    return (
                        <Matrix key={'matrix-' + matrixIndex} title={'ABC'} onChange={this.handleChange}>
                            {Object.keys(this.availablePermissions).map((permissionKey, rowIndex) => {
                                return (
                                    <Matrix.Row key={'row-' + rowIndex} name={permission.name}>
                                        {permission.xyz.map((permissionItem, itemIndex) => (
                                            <Matrix.Item key={'item-' + itemIndex} name={permissionItem} icon="su-edit"/>
                                        ))}
                                    </Matrix.Row>
                                );
                            })}
                        </Matrix>
                    );
                })}
            </div>
        );
    }
}
