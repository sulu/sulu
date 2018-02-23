// @flow
import React from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import classNames from 'classnames';
import {translate} from '../../utils';
import type {Error, ErrorCollection} from '../../types';
import fieldRegistry from './registries/FieldRegistry';
import fieldStyles from './field.scss';
import type {SchemaEntry} from './types';

type Props = {
    error?: Error | ErrorCollection,
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

    findErrorKeyword(error: ?Error | ErrorCollection): ?string {
        if (!error) {
            return;
        }

        if (Array.isArray(error)) {
            return;
        }

        if (typeof error.keyword === 'string') {
            return error.keyword;
        }

        for (const childKey in error) {
            return this.findErrorKeyword(error[childKey]);
        }
    }

    render() {
        const {error, value, locale, schema, showAllErrors} = this.props;
        const {label, maxOccurs, minOccurs, options, required, type, types} = schema;
        const FieldType = fieldRegistry.get(type);

        const fieldClass = classNames(
            fieldStyles.field,
            {
                [fieldStyles.error]: !!error,
            }
        );

        const errorKeyword = this.findErrorKeyword(error);

        return (
            <div className={fieldClass}>
                <label className={fieldStyles.label}>{label}{required && ' *'}</label>
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
                <label className={fieldStyles.errorLabel}>
                    {errorKeyword && translate('sulu_admin.error_' + errorKeyword.toLowerCase())}
                </label>
            </div>
        );
    }
}
