// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import log from 'loglevel';
import Router from '../../services/Router';
import {translate} from '../../utils';
import FieldComponent from '../../components/Form/Field';
import fieldRegistry from './registries/fieldRegistry';
import fieldStyles from './field.scss';
import FormInspector from './FormInspector';
import type {Error, ErrorCollection, SchemaEntry} from './types';

type Props = {|
    dataPath: string,
    error?: Error | ErrorCollection,
    formInspector: FormInspector,
    name: string,
    onChange: (string, *) => void,
    onFinish: (dataPath: string, schemaPath: string) => void,
    onSuccess: ?() => void,
    router: ?Router,
    schema: SchemaEntry,
    schemaPath: string,
    showAllErrors: boolean,
    value?: *,
|};

@observer
class Field extends React.Component<Props> {
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

        // if the fields are nested the field on every path should be finished
        if (subDataPath && subSchemaPath) {
            onFinish(subDataPath, subSchemaPath);
        }

        onFinish(dataPath, schemaPath);
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

    @computed.struct get types() {
        return this.props.schema.types;
    }

    render() {
        const {
            dataPath,
            error,
            formInspector,
            name,
            onSuccess,
            router,
            schema,
            schemaPath,
            showAllErrors,
            value,
        } = this.props;

        const {
            defaultType,
            description,
            disabled,
            label,
            maxOccurs,
            minOccurs,
            onInvalid,
            options: schemaOptions = {},
            required,
            type,
        } = schema;

        let FieldType;

        try {
            FieldType = fieldRegistry.get(type);
        } catch (e) {
            if (onInvalid === 'ignore') {
                return null;
            }

            log.error(e);

            return (
                <FieldComponent
                    colSpan={schema.colSpan}
                    spaceAfter={schema.spaceAfter}
                >
                    <div className={fieldStyles.fieldContainer}>
                        <div className={fieldStyles.field}>
                            <div className={fieldStyles.fieldException}>
                                <h4>Error while rendering field!</h4>
                                <p>
                                    <b>Name:</b> {name}<br />
                                    <b>Exception:</b> {e.toString()}
                                </p>
                            </div>
                        </div>
                    </div>
                </FieldComponent>
            );
        }
        const fieldTypeOptions = fieldRegistry.getOptions(type);

        const errorKeyword = this.findErrorKeyword(error);

        return (
            <FieldComponent
                colSpan={schema.colSpan}
                description={description}
                error={errorKeyword ? translate('sulu_admin.error_' + errorKeyword.toLowerCase()) : undefined}
                id={dataPath}
                label={label}
                required={required}
                spaceAfter={schema.spaceAfter}
            >
                <div className={fieldStyles.fieldContainer}>
                    <div className={fieldStyles.field}>
                        <FieldType
                            dataPath={dataPath}
                            defaultType={defaultType}
                            disabled={disabled}
                            error={error}
                            fieldTypeOptions={fieldTypeOptions}
                            formInspector={formInspector}
                            label={label || name}
                            maxOccurs={maxOccurs}
                            minOccurs={minOccurs}
                            onChange={this.handleChange}
                            onFinish={this.handleFinish}
                            onSuccess={onSuccess}
                            router={router}
                            schemaOptions={schemaOptions}
                            schemaPath={schemaPath}
                            showAllErrors={showAllErrors}
                            types={this.types}
                            value={value}
                        />
                    </div>
                </div>
            </FieldComponent>
        );
    }
}

export default Field;
