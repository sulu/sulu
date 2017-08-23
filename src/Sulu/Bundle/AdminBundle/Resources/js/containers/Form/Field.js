// @flow
import React from 'react';
import fieldStore from './stores/FieldStore';
import type {SchemaEntry} from './types';

type Props = {
    schema: SchemaEntry,
};

export default class Field extends React.PureComponent<Props> {
    render() {
        const {label, type} = this.props.schema;
        const FieldType = fieldStore.get(type);

        return (
            <div>
                <label>{label}</label>
                <FieldType />
            </div>
        );
    }
}
