// @flow
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import React from 'react';
import Divider from '../../components/Divider';
import Grid from '../../components/Grid';
import Field from './Field';
import rendererStyles from './renderer.scss';
import type {Schema, SchemaEntry} from './types';

type Props = {
    data: Object,
    schema: Schema,
    onChange: (string, *) => void,
    locale: ?IObservableValue<string>,
};

@observer
export default class Renderer extends React.Component<Props> {
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
        const {data, locale, onChange} = this.props;

        return (
            <Grid.Item
                className={rendererStyles.gridItem}
                key={schemaKey}
                size={schemaField.size}
                spaceAfter={schemaField.spaceAfter}
            >
                <Field
                    name={schemaKey}
                    schema={schemaField}
                    onChange={onChange}
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
