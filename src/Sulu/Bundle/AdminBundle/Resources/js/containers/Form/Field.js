// @flow
import React from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import fieldRegistry from './registries/FieldRegistry';
import fieldStyles from './field.scss';
import type {SchemaEntry} from './types';

type Props = {
    name: string,
    value?: mixed,
    schema: SchemaEntry,
    onChange: (string, mixed) => void,
    locale?: ?IObservableValue<string>,
};

export default class Field extends React.PureComponent<Props> {
    handleChange = (value: mixed) => {
        const {name, onChange} = this.props;

        onChange(name, value);
    };

    render() {
        const {
            value,
            locale,
            schema,
        } = this.props;
        const {label, options, type} = schema;
        const FieldType = fieldRegistry.get(type);

        return (
            <div>
                <label className={fieldStyles.label}>{label}</label>
                <FieldType
                    onChange={this.handleChange}
                    options={options}
                    value={value}
                    locale={locale}
                />
            </div>
        );
    }
}
