// @flow
import {action} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import Form from '../../components/Form';
import Field from './Field';
import FormInspector from './FormInspector';
import type {ErrorCollection, Schema, SchemaEntry} from './types';

type Props = {|
    data: Object,
    dataPath: string,
    errors?: ErrorCollection,
    formInspector: FormInspector,
    schema: Schema,
    schemaPath: string,
    showAllErrors: boolean,
    onChange: (string, *) => void,
    onFieldFinish: ?(dataPath: string, schemaPath: string) => void,
|};

@observer
export default class Renderer extends React.Component<Props> {
    static defaultProps = {
        showAllErrors: false,
    };

    @action handleFieldFinish = (dataPath: string, schemaPath: string) => {
        const {onFieldFinish} = this.props;

        if (onFieldFinish) {
            onFieldFinish(dataPath, schemaPath);
        }
    };

    renderSection(schemaField: SchemaEntry, schemaKey: string, schemaPath: string) {
        const {colSpan, label, items} = schemaField;
        return (
            <Form.Section colSpan={colSpan} key={schemaKey} label={label}>
                {!!items &&
                    Object.keys(items).map((key) => this.renderItem(items[key], key, schemaPath + '/items/' + key))
                }
            </Form.Section>
        );
    }

    renderField(schemaField: SchemaEntry, schemaKey: string, schemaPath: string) {
        const {data, dataPath, errors, formInspector, onChange, showAllErrors} = this.props;
        const itemDataPath = dataPath + '/' + schemaKey;

        const error = (showAllErrors || formInspector.isFieldModified(itemDataPath)) && errors && errors[schemaKey]
            ? errors[schemaKey]
            : undefined;

        return (
            <Field
                dataPath={itemDataPath}
                error={error}
                formInspector={formInspector}
                key={schemaKey}
                name={schemaKey}
                onChange={onChange}
                onFinish={this.handleFieldFinish}
                schema={schemaField}
                schemaPath={schemaPath}
                showAllErrors={showAllErrors}
                value={data[schemaKey]}
            />
        );
    }

    renderItem(schemaField: SchemaEntry, schemaKey: string, schemaPath: string) {
        if (schemaField.visible === false) {
            return null;
        }

        if (schemaField.type === 'section') {
            return this.renderSection(schemaField, schemaKey, schemaPath);
        }

        return this.renderField(schemaField, schemaKey, schemaPath);
    }

    render() {
        const {
            schema,
            schemaPath,
        } = this.props;
        const schemaKeys = Object.keys(schema);

        return (
            <Form>
                {schemaKeys.map((schemaKey) => this.renderItem(
                    schema[schemaKey],
                    schemaKey,
                    schemaPath + '/' + schemaKey
                ))}
            </Form>
        );
    }
}
