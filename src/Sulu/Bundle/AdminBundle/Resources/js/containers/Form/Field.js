// @flow
import React from 'react';
import fieldStore from './stores/FieldStore';
import fieldStyles from './field.scss';
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
                <label className={fieldStyles.label}>{label}</label>
                <FieldType />
            </div>
        );
    }
}
