// @flow
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import React from 'react';
import Divider from '../../components/Divider';
import Grid from '../../components/Grid';
import type {ErrorCollection} from '../../types';
import Field from './Field';
import rendererStyles from './renderer.scss';
import type {Schema, SchemaEntry} from './types';

type Props = {
    data: Object,
    errors?: ErrorCollection,
    schema: Schema,
    showAllErrors: boolean,
    onChange: (string, *) => void,
    onFieldFinish: () => void,
    locale: ?IObservableValue<string>,
};

@observer
export default class Renderer extends React.Component<Props> {
    static defaultProps = {
        showAllErrors: false,
    };

    @observable modifiedFields: Array<string> = [];

    @action handleFieldFinish = (name: string) => {
        const {onFieldFinish} = this.props;
        const {modifiedFields} = this;

        if (!modifiedFields.includes(name)) {
            modifiedFields.push(name);
        }

        onFieldFinish();
    };

    renderGridSection(schemaField: SchemaEntry, schemaKey: string) {
        const {items} = schemaField;
        return (
            <Grid.Section key={schemaKey} className={rendererStyles.gridSection}>
                <Grid.Item size={12}>
                    <Divider>
                        {schemaField.label}
                    </Divider>
                </Grid.Item>
                {items &&
                    Object.keys(items).map((key) => this.renderItem(items[key], key))
                }
            </Grid.Section>
        );
    }

    renderGridItem(schemaField: SchemaEntry, schemaKey: string) {
        const {data, errors, locale, onChange, showAllErrors} = this.props;

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
                    name={schemaKey}
                    schema={schemaField}
                    onChange={onChange}
                    onFinish={this.handleFieldFinish}
                    showAllErrors={showAllErrors}
                    value={data[schemaKey]}
                    locale={locale}
                />
            </Grid.Item>
        );
    }

    renderItem(schemaField: SchemaEntry, schemaKey: string) {
        if (schemaField.type === 'section') {
            return this.renderGridSection(schemaField, schemaKey);
        }

        return this.renderGridItem(schemaField, schemaKey);
    }

    render() {
        const {
            schema,
        } = this.props;
        const schemaKeys = Object.keys(schema);

        return (
            <Grid>
                {schemaKeys.map((schemaKey) => this.renderItem(schema[schemaKey], schemaKey))}
            </Grid>
        );
    }
}
