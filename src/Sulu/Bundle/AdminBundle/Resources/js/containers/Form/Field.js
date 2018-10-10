// @flow
import React from 'react';
import log from 'loglevel';
import {translate} from '../../utils';
import type {Error, ErrorCollection} from '../../types';
import FieldComponent from '../../components/Form/Field';
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
        const {name, onChange, schema} = this.props;

        if (schema.disabled) {
            return;
        }

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

        if (error.keyword === 'const') {
            // the const validation only makes sense in combination with other dependant constraints, since it would
            // not make sense to have a field with a single value with no possibility to change it
            // therefore we are only showing the other errors, since the const error would just confuse users
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
        const {
            description,
            disabled,
            label,
            maxOccurs,
            minOccurs,
            options: schemaOptions,
            required,
            type,
            types,
        } = schema;

        let FieldType;

        try {
            FieldType = fieldRegistry.get(type);
        } catch (e) {
            log.error(e);

            return (
                <FieldComponent
                    size={schema.size}
                    spaceAfter={schema.spaceAfter}
                >
                    <div className={fieldStyles.fieldException}>
                        <h4>Error while rendering field!</h4>
                        <p>
                            <b>Name:</b> {name}<br />
                            <b>Exception:</b> {e.toString()}
                        </p>
                    </div>
                </FieldComponent>
            );
        }
        const fieldTypeOptions = fieldRegistry.getOptions(type);

        const errorKeyword = this.findErrorKeyword(error);

        return (
            <FieldComponent
                description={description}
                error={errorKeyword ? translate('sulu_admin.error_' + errorKeyword.toLowerCase()) : undefined}
                inputId={dataPath}
                label={label}
                required={required}
                size={schema.size}
                spaceAfter={schema.spaceAfter}
            >
                <FieldType
                    dataPath={dataPath}
                    disabled={disabled}
                    error={error}
                    fieldTypeOptions={fieldTypeOptions}
                    formInspector={formInspector}
                    label={label || name}
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
            </FieldComponent>
        );
    }
}
