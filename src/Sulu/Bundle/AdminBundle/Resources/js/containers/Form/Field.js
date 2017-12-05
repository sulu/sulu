// @flow
import React from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import type {SchemaEntry} from '../../stores/ResourceStore/types';
import fieldRegistry from './registries/FieldRegistry';
import fieldStyles from './field.scss';

type Props = {
    name: string,
    value?: mixed,
    schema: SchemaEntry,
    onChange: (string, mixed) => void,
    locale: ?IObservableValue<string>,
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
        const {label, type} = schema;
        const FieldType = fieldRegistry.get(type);

        return (
            <div>
                <label className={fieldStyles.label}>{label}</label>
                <FieldType
                    onChange={this.handleChange}
                    value={value}
                    locale={locale}
                />
            </div>
        );
    }
}
