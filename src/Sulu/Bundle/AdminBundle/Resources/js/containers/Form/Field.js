// @flow
import React from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import fieldRegistry from './registries/FieldRegistry';
import fieldStyles from './field.scss';
import type {SchemaEntry} from './types';

type Props = {
    name: string,
    value?: *,
    schema: SchemaEntry,
    onChange: (string, *) => void,
    locale?: ?IObservableValue<string>,
};

export default class Field extends React.PureComponent<Props> {
    handleChange = (value: *) => {
        const {name, onChange} = this.props;

        onChange(name, value);
    };

    render() {
        const {
            value,
            locale,
            schema,
        } = this.props;
        const {label, maxOccurs, minOccurs, options, type, types} = schema;
        const FieldType = fieldRegistry.get(type);

        return (
            <div>
                <label className={fieldStyles.label}>{label}</label>
                <FieldType
                    maxOccurs={maxOccurs}
                    minOccurs={minOccurs}
                    locale={locale}
                    onChange={this.handleChange}
                    options={options}
                    types={types}
                    value={value}
                />
            </div>
        );
    }
}
