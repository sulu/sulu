// @flow
import React from 'react';
import AssignmentComponent from '../../Assignment';
import type {FieldTypeProps} from '../../../types';

export default class Assignment extends React.Component<FieldTypeProps<Array<string | number>>> {
    render() {
        return <AssignmentComponent />;
    }
}
