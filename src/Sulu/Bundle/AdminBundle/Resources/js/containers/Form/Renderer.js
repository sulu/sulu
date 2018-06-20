// @flow
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import Divider from '../../components/Divider';
import Grid from '../../components/Grid';
import type {ErrorCollection} from '../../types';
import Field from './Field';
import FormInspector from './FormInspector';
import rendererStyles from './renderer.scss';
import type {Schema, SchemaEntry} from './types';

type Props = {|
    data: Object,
    errors?: ErrorCollection,
    formInspector: FormInspector,
    schema: Schema,
    schemaPath: string,
    showAllErrors: boolean,
    onChange: (string, *) => void,
    onFieldFinish: ?(schemaPath: string) => void,
|};

@observer
export default class Renderer extends React.Component<Props> {
    static defaultProps = {
        showAllErrors: false,
    };

    @observable modifiedFields: Array<string> = [];

    @action handleFieldFinish = (name: string, schemaPath: string) => {
        const {onFieldFinish} = this.props;
        const {modifiedFields} = this;

        if (!modifiedFields.includes(name)) {
            modifiedFields.push(name);
        }

        if(onFieldFinish) {
            onFieldFinish(schemaPath);
        }
    };

    renderGridSection(schemaField: SchemaEntry, schemaKey: string, schemaPath: string) {
        const {items, size} = schemaField;
        return (
            <Grid.Section key={schemaKey} className={rendererStyles.gridSection} size={size}>
                {schemaField.label &&
                    <Grid.Item size={12}>
                        <Divider>
                            {schemaField.label}
                        </Divider>
                    </Grid.Item>
                }
                {items &&
                Object.keys(items).map((key) => this.renderItem(items[key], key, schemaPath + '/items/' + key))
                }
            </Grid.Section>
        );
    }

    renderGridItem(schemaField: SchemaEntry, schemaKey: string, schemaPath: string) {
        const {data, errors, formInspector, onChange, showAllErrors} = this.props;

        const error = (showAllErrors || this.modifiedFields.includes(schemaKey)) && errors && errors[schemaKey]
            ? errors[schemaKey]
            : undefined;

        return (
            <Grid.Item
                className={rendererStyles.gridItem}
                key={schemaKey}
                size={schemaField.size}
                spaceAfter={schemaField.spaceAfter}
            >
                <Field
                    error={error}
                    formInspector={formInspector}
                    name={schemaKey}
                    onChange={onChange}
                    onFinish={this.handleFieldFinish}
                    schema={schemaField}
                    schemaPath={schemaPath}
                    showAllErrors={showAllErrors}
                    value={data[schemaKey]}
                />
            </Grid.Item>
        );
    }

    renderItem(schemaField: SchemaEntry, schemaKey: string, schemaPath: string) {
        if (schemaField.type === 'section') {
            return this.renderGridSection(schemaField, schemaKey, schemaPath);
        }

        return this.renderGridItem(schemaField, schemaKey, schemaPath);
    }

    render() {
        const {
            schema,
            schemaPath,
        } = this.props;
        const schemaKeys = Object.keys(schema);

        return (
            <Grid className={rendererStyles.grid}>
                {schemaKeys.map(
                    (schemaKey) => this.renderItem(schema[schemaKey], schemaKey, schemaPath + '/' + schemaKey)
                )}
            </Grid>
        );
    }
}
