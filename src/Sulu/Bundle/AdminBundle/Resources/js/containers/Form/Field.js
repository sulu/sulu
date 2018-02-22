// @flow
import React from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import classNames from 'classnames';
import type {Error} from '../../types';
import fieldRegistry from './registries/FieldRegistry';
import fieldStyles from './field.scss';
import type {SchemaEntry} from './types';

type Props = {
    error?: Error,
    name: string,
    value?: *,
    schema: SchemaEntry,
    onChange: (string, *) => void,
    onFinish: () => void,
    locale?: ?IObservableValue<string>,
};

export default class Field extends React.PureComponent<Props> {
    handleChange = (value: *) => {
        const {name, onChange} = this.props;

        onChange(name, value);
    };

    handleFinish = () => {
        this.props.onFinish();
    };

    render() {
        const {
            error,
            value,
            locale,
            schema,
        } = this.props;
        const {label, maxOccurs, minOccurs, options, required, type, types} = schema;
        const FieldType = fieldRegistry.get(type);

        const labelClass = classNames(
            fieldStyles.label,
            {
                [fieldStyles.error]: error,
            }
        );

        // TODO use error.keyword to write an error message
        return (
            <div>
                <label className={labelClass}>{label}{required && ' *'}</label>
                <FieldType
                    error={error}
                    maxOccurs={maxOccurs}
                    minOccurs={minOccurs}
                    locale={locale}
                    onChange={this.handleChange}
                    onFinish={this.handleFinish}
                    options={options}
                    types={types}
                    value={value}
                />
            </div>
        );
    }
}
