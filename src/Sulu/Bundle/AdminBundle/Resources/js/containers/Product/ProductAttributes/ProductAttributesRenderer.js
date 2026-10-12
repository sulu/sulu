// @flow
import React, {Fragment} from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import CollapsibleCollection from '../../../components/CollapsibleCollection';
import Icon from '../../../components/Icon';
import Table from '../../../components/Table';
import Router from '../../../services/Router';
import Field from '../../Form/Field';
import FormInspector from '../../Form/FormInspector';
import AttributeGroupTable from '../AttributeGroupTable';
import productAttributesRendererStyles from './productAttributesRenderer.scss';
import type {Node} from 'react';
import type {Error, ErrorCollection, Schema, SchemaEntry} from '../../Form/types';

export type Row = {
    name: string,
    schema: SchemaEntry,
};

// subtitle is never set, CollapsibleConfig asks for the key
type Group = {
    id: string,
    rows: Array<Row>,
    sectionKey: string,
    subtitle?: string,
    title: string,
};

type Props = {|
    allExpanded: boolean,
    data: Object,
    disabled: boolean,
    errors: {[string]: Error | ErrorCollection},
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
 * are shown is decided by the given filterItem callback, allExpanded expands or collapses all groups.
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

                return {id: sectionKey, rows, sectionKey, title: section.label || ''};
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

    // The row carries the marker once, for a section when one of its items is required.
    isRequired(schema: SchemaEntry): boolean {
        const {items} = schema;

        return !!schema.required
            || (!!items && Object.keys(items).some((itemName) => !!items[itemName].required));
    }

    // A change of one item emits the whole object of its row, because the row's value is stored as one.
    createItemChangeHandler(rowName: string) {
        const {formInspector, onChange} = this.props;

        return (itemName: string, itemValue: mixed) => onChange(
            rowName,
            {...formInspector.getValueByPath('/' + rowName), [itemName]: itemValue}
        );
    }

    // An attribute with several properties arrives as a section; its value is one object keyed by the item names.
    renderSectionItems(row: Row, sectionKey: string) {
        const {data, disabled, errors, formInspector, onFinish, router} = this.props;
        const {name, schema} = row;
        const rowDataPath = '/' + name;
        const rowErrors: ?Object = errors[name];
        const items = schema.items || {};

        return (
            <div className={productAttributesRendererStyles.sectionItems}>
                {Object.keys(items).map((itemName) => {
                    const itemDataPath = rowDataPath + '/' + itemName;
                    const itemSchema = items[itemName];

                    return (
                        <Field
                            data={data}
                            dataPath={itemDataPath}
                            error={rowErrors ? rowErrors[itemName] : undefined}
                            formInspector={formInspector}
                            key={itemName}
                            name={itemName}
                            onChange={this.createItemChangeHandler(name)}
                            onFinish={onFinish}
                            onSuccess={undefined}
                            router={router}
                            schema={{
                                ...itemSchema,
                                colSpan: 12,
                                disabledCondition: disabled ? 'true' : itemSchema.disabledCondition,
                                required: false,
                            }}
                            schemaPath={'/' + sectionKey + '/items/' + name + '/items/' + itemName}
                            value={formInspector.getValueByPath(itemDataPath)}
                        />
                    );
                })}
            </div>
        );
    }

    renderRow(row: Row, sectionKey: string) {
        const {data, errors, formInspector, onChange, onFinish, router} = this.props;
        const {name, schema} = row;
        const rowDataPath = '/' + name;

        return (
            <Table.Row id={name} key={name}>
                <Table.Cell className={productAttributesRendererStyles.labelCell}>
                    {schema.label}{this.isRequired(schema) ? ' *' : ''}
                </Table.Cell>
                <Table.Cell className={productAttributesRendererStyles.fieldCell}>
                    {schema.type === 'section' && schema.items
                        ? this.renderSectionItems(row, sectionKey)
                        : <Field
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
                    }
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
        const {allExpanded, toolbar} = this.props;
        const {groups} = this;

        return (
            <Fragment>
                <CollapsibleCollection
                    allExpanded={allExpanded}
                    movable={false}
                    onChange={this.handleCollectionChange}
                    renderCollapsibleContent={this.renderCollapsibleContent}
                    toolbar={toolbar}
                    value={groups}
                />
                {groups.length === 0 &&
                    <div className={productAttributesRendererStyles.placeholder}>
                        <Icon className={productAttributesRendererStyles.placeholderIcon} name="su-battery-low" />
                    </div>
                }
            </Fragment>
        );
    }
}

export default ProductAttributesRenderer;
