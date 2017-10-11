// @flow
import React from 'react';
import fieldRegistry from './registries/FieldRegistry';
import fieldStyles from './field.scss';
import type {SchemaEntry} from './types';

type Props = {
    name: string,
    value?: mixed,
    schema: SchemaEntry,
    onChange: (string, mixed) => void,
};

export default class Field extends React.PureComponent<Props> {
    handleChange = (value: mixed) => {
        const {name, onChange} = this.props;

        onChange(name, value);
    };

    render() {
        const {schema, value} = this.props;
        const {label, type} = schema;
        const FieldType = fieldRegistry.get(type);

        return (
            <div>
                <label className={fieldStyles.label}>{label}</label>
                <FieldType onChange={this.handleChange} value={value} />
            </div>
        );
    }
}
