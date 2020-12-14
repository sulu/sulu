// @flow
import React from 'react';
import type {Element} from 'react';
import {action} from 'mobx';
import {observer} from 'mobx-react';
import jsonpointer from 'json-pointer';
import Form from '../../components/Form';
import Router from '../../services/Router';
import Field from './Field';
import FormInspector from './FormInspector';
import Section from './Section';
import type {ErrorCollection, Schema, SchemaEntry} from './types';

type Props = {|
    data: Object,
    dataPath: string,
    errors?: ErrorCollection,
    formInspector: FormInspector,
    onChange: (string, *) => void,
    onFieldFinish: ?(dataPath: string, schemaPath: string) => void,
    onSuccess: ?() => void,
    router: ?Router,
    schema: Schema,
    schemaPath: string,
    showAllErrors: boolean,
|};

@observer
class Renderer extends React.Component<Props> {
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
        const {data, formInspector} = this.props;
        const {items} = schemaField;

        return (
            <Section data={data} formInspector={formInspector} key={schemaKey} name={schemaKey} schema={schemaField}>
                {!!items &&
                    Object.keys(items).map((key) => this.renderItem(items[key], key, schemaPath + '/items/' + key))
                }
            </Section>
        );
    }

    renderField(schemaField: SchemaEntry, schemaKey: string, schemaPath: string) {
        const {data, dataPath, errors, formInspector, onChange, onSuccess, router, showAllErrors} = this.props;
        const itemDataPath = dataPath + '/' + schemaKey;

        const error = (showAllErrors || formInspector.isFieldModified(itemDataPath)) && errors && errors[schemaKey]
            ? errors[schemaKey]
            : undefined;

        return (
            <Field
                data={data}
                dataPath={itemDataPath}
                error={error}
                formInspector={formInspector}
                key={schemaKey}
                name={schemaKey}
                onChange={onChange}
                onFinish={this.handleFieldFinish}
                onSuccess={onSuccess}
                router={router}
                schema={schemaField}
                schemaPath={schemaPath}
                showAllErrors={showAllErrors}
                value={jsonpointer.has(data, '/' + schemaKey) ? jsonpointer.get(data, '/' + schemaKey) : undefined}
            />
        );
    }

    renderItem(
        schemaField: SchemaEntry,
        schemaKey: string,
        schemaPath: string
    ): ?Element<typeof Field | typeof Section> {
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

export default Renderer;
