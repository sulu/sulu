// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import PermissionsContainer from '../../Permissions';
import type {ContextPermission} from '../../Permissions';

type Props = FieldTypeProps<?Array<ContextPermission>>;

@observer
export default class Permissions extends React.Component<Props> {
    @computed get system(): string {
        const {formInspector} = this.props;
        const system = formInspector.getValueByPath('/system');

        if (!system || typeof system !== 'string') {
            throw new Error('Value "system" needs to be provided as string');
        }

        return system;
    }

    handleChange = (value: Array<ContextPermission>) => {
        const {onChange, onFinish} = this.props;
        onChange(value);
        onFinish();
    };

    render() {
        const {value} = this.props;

        return (
            <PermissionsContainer onChange={this.handleChange} system={this.system} value={value ? value : []} />
        );
    }
}
