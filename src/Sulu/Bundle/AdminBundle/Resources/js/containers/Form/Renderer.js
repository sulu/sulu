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

    @observable modifiedFields: Array<string> = [];

    @action handleFieldFinish = (dataPath: string, schemaPath: string) => {
        const {onFieldFinish} = this.props;
        const {modifiedFields} = this;

        if (!modifiedFields.includes(dataPath)) {
            modifiedFields.push(dataPath);
        }

        if(onFieldFinish) {
            onFieldFinish(dataPath, schemaPath);
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
                Object.keys(items).map(
                    (key) => this.renderItem(items[key], key, schemaPath + '/items/' + key)
                )}
            </Grid.Section>
        );
    }

    renderGridItem(schemaField: SchemaEntry, schemaKey: string, schemaPath: string) {
        const {data, dataPath, errors, formInspector, onChange, showAllErrors} = this.props;
        const itemDataPath = dataPath + '/' + schemaKey;

        const error = (showAllErrors || this.modifiedFields.includes(itemDataPath)) && errors && errors[schemaKey]
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
                    dataPath={itemDataPath}
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
                    (schemaKey) => this.renderItem(
                        schema[schemaKey],
                        schemaKey,
                        schemaPath + '/' + schemaKey
                    )
                )}
            </Grid>
        );
    }
}
