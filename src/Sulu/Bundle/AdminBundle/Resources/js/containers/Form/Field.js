// @flow
import React from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import classNames from 'classnames';
import {translate} from '../../utils';
import type {Error} from '../../types';
import fieldRegistry from './registries/FieldRegistry';
import fieldStyles from './field.scss';
import type {SchemaEntry} from './types';

type Props = {
    error?: Error,
    locale?: ?IObservableValue<string>,
    name: string,
    onChange: (string, *) => void,
    onFinish: (name: string) => void,
    schema: SchemaEntry,
    showAllErrors: boolean,
    value?: *,
};

export default class Field extends React.PureComponent<Props> {
    static defaultProps = {
        showAllErrors: false,
    };

    handleChange = (value: *) => {
        const {name, onChange} = this.props;

        onChange(name, value);
    };

    handleFinish = () => {
        this.props.onFinish(this.props.name);
    };

    render() {
        const {error, value, locale, schema, showAllErrors} = this.props;
        const {label, maxOccurs, minOccurs, options, required, type, types} = schema;
        const FieldType = fieldRegistry.get(type);

        const labelClass = classNames(
            fieldStyles.label,
            {
                [fieldStyles.error]: !!error,
            }
        );

        const errorKeyword = error && !Array.isArray(error) && error.keyword;

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
                    showAllErrors={showAllErrors}
                    types={types}
                    value={value}
                />
                {errorKeyword &&
                    <label className={fieldStyles.errorLabel}>
                        {translate('sulu_admin.error_' + errorKeyword.toLowerCase())}
                    </label>
                }
            </div>
        );
    }
}
