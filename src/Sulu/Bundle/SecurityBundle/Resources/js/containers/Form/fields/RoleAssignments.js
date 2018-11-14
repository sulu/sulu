// @flow
import React from 'react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import RoleAssignmentsContainer from '../../RoleAssignments';

type Props = FieldTypeProps<?Array<Object>>;

export default class RoleAssignments extends React.Component<Props> {
    handleChange = (value: Array<Object>) => {
        const {onChange, onFinish} = this.props;
        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, value} = this.props;

        return (
            <RoleAssignmentsContainer disabled={!!disabled} onChange={this.handleChange} value={value ? value : []} />
        );
    }
}
