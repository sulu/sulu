// @flow
import React from 'react';
import log from 'loglevel';
import classNames from 'classnames';
import {translate} from '../../utils';
import type {Error, ErrorCollection} from '../../types';
import fieldRegistry from './registries/FieldRegistry';
import fieldStyles from './field.scss';
import FormInspector from './FormInspector';
import type {SchemaEntry} from './types';

type Props = {|
    dataPath: string,
    error?: Error | ErrorCollection,
    formInspector: FormInspector,
    name: string,
    onChange: (string, *) => void,
    onFinish: (dataPath: string, schemaPath: string) => void,
    schema: SchemaEntry,
    schemaPath: string,
    showAllErrors: boolean,
    value?: *,
|};

export default class Field extends React.Component<Props> {
    static defaultProps = {
        showAllErrors: false,
    };

    handleChange = (value: *) => {
        const {name, onChange} = this.props;

        onChange(name, value);
    };

    handleFinish = (subDataPath: ?string, subSchemaPath: ?string) => {
        const {dataPath, onFinish, schemaPath} = this.props;
        // if the fields are nested the deepest field passes its pathes up
        onFinish(subDataPath || dataPath, subSchemaPath || schemaPath);
    };

    findErrorKeyword(error: ?Error | ErrorCollection): ?string {
        if (!error) {
            return;
        }

        if (Array.isArray(error)) {
            // this happens when the error is in a block field type
            // since the error is shown on the child elements of the block we do not have to mark the block separately
            return;
        }

        if (typeof error.keyword === 'string') {
            return error.keyword;
        }

        for (const childKey in error) {
            // this happens when it is an error collection and not a single error
            // we will find the first child error with a keyword recursively
            return this.findErrorKeyword(error[childKey]);
        }
    }

    render() {
        const {dataPath, error, value, formInspector, schema, schemaPath, showAllErrors, name} = this.props;
        const {label, maxOccurs, minOccurs, options: schemaOptions, required, type, types} = schema;
        let FieldType;

        try {
            FieldType = fieldRegistry.get(type);
        } catch (e) {
            log.error(e);

            return (
                <div className={fieldStyles.fieldException}>
                    <h4>Error while rendering field!</h4>
                    <p>
                        <b>Name:</b> {name}<br />
                        <b>Exception:</b> {e.toString()}
                    </p>
                </div>
            );
        }
        const fieldTypeOptions = fieldRegistry.getOptions(type);

        const fieldClass = classNames(
            fieldStyles.field,
            {
                [fieldStyles.error]: !!error,
            }
        );

        const errorKeyword = this.findErrorKeyword(error);

        return (
            <div className={fieldClass}>
                {label && <label className={fieldStyles.label}>{label}{required && ' *'}</label>}
                <FieldType
                    dataPath={dataPath}
                    error={error}
                    fieldTypeOptions={fieldTypeOptions}
                    formInspector={formInspector}
                    maxOccurs={maxOccurs}
                    minOccurs={minOccurs}
                    onChange={this.handleChange}
                    onFinish={this.handleFinish}
                    schemaOptions={schemaOptions}
                    schemaPath={schemaPath}
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
