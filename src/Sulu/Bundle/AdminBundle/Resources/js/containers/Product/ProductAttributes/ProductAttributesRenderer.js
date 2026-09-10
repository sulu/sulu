// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import CollapsibleCollection from '../../../components/CollapsibleCollection';
import Table from '../../../components/Table';
import Router from '../../../services/Router';
import Field from '../../Form/Field';
import FormInspector from '../../Form/FormInspector';
import AttributeGroupTable from '../AttributeGroupTable';
import productAttributesRendererStyles from './productAttributesRenderer.scss';
import type {Node} from 'react';
import type {Error, Schema, SchemaEntry} from '../../Form/types';

export type Row = {
    name: string,
    schema: SchemaEntry,
};

// subtitle is never set, CollapsibleConfig asks for the key
type Group = {
    rows: Array<Row>,
    sectionKey: string,
    subtitle?: string,
    title: string,
};

type Props = {|
    data: Object,
    disabled: boolean,
    errors: {[string]: Error},
    filterItem: (row: Row) => boolean,
    formInspector: FormInspector,
    onChange: (name: string, value: mixed) => void,
    onFinish: (dataPath: string, schemaPath: string) => void,
    router: ?Router,
    schema: Schema,
    toolbar?: Node,
|};

/**
 * Renders a product_attributes form as one collapsible card per attribute group with a label/field
 * row per attribute. Each row uses the form's Field, so any field type works. Values come from the
 * given form inspector, the one of the container's own store, errors from the errors map. Which rows
 * are shown is decided by the given filterItem callback.
 *
 * @experimental We can not yet give BC Promise for this new container in Sulu 3.1.
 */
@observer
class ProductAttributesRenderer extends React.Component<Props> {
    @computed get groups(): Array<Group> {
        const {filterItem, schema} = this.props;

        return Object.keys(schema)
            .map((sectionKey) => {
                const section = schema[sectionKey];
                const items = section.items || {};

                const rows = Object.keys(items)
                    .map((name) => ({name, schema: items[name]}))
                    .filter((row) => filterItem(row));

                return {rows, sectionKey, title: section.label || ''};
            })
            .filter((group) => group.rows.length > 0);
    }

    // The row draws the label; Form.Field would draw it a second time above the input.
    rowSchema(schema: SchemaEntry): SchemaEntry {
        const {disabled} = this.props;
        const {label, ...rest} = schema;

        return {
            ...rest,
            colSpan: 12,
            disabledCondition: disabled ? 'true' : schema.disabledCondition,
        };
    }

    renderRow(row: Row, sectionKey: string) {
        const {data, errors, formInspector, onChange, onFinish, router} = this.props;
        const {name, schema} = row;
        const rowDataPath = '/' + name;

        return (
            <Table.Row id={name} key={name}>
                <Table.Cell className={productAttributesRendererStyles.labelCell}>
                    {schema.label}{schema.required ? ' *' : ''}
                </Table.Cell>
                <Table.Cell className={productAttributesRendererStyles.fieldCell}>
                    <Field
                        data={data}
                        dataPath={rowDataPath}
                        error={errors[name]}
                        formInspector={formInspector}
                        name={name}
                        onChange={onChange}
                        onFinish={onFinish}
                        onSuccess={undefined}
                        router={router}
                        schema={this.rowSchema(schema)}
                        schemaPath={'/' + sectionKey + '/items/' + name}
                        value={formInspector.getValueByPath(rowDataPath)}
                    />
                </Table.Cell>
            </Table.Row>
        );
    }

    renderCollapsibleContent = (group: Group) => {
        return (
            <AttributeGroupTable>
                {group.rows.map((row) => this.renderRow(row, group.sectionKey))}
            </AttributeGroupTable>
        );
    };

    handleCollectionChange = () => {};

    render() {
        return (
            <CollapsibleCollection
                movable={false}
                onChange={this.handleCollectionChange}
                renderCollapsibleContent={this.renderCollapsibleContent}
                toolbar={this.props.toolbar}
                value={this.groups}
            />
        );
    }
}

export default ProductAttributesRenderer;
