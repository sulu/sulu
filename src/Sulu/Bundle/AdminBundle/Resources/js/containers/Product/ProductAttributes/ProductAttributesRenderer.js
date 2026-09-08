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
import {NAME_PREFIX} from './namePrefix';
import type {Node} from 'react';
import type {Error, Schema, SchemaEntry} from '../../Form/types';

type Row = {
    name: string,
    schema: SchemaEntry,
};

type Group = {
    rows: Array<Row>,
    sectionKey: string,
    subtitle?: string,
    title: string,
};

type Props = {|
    data: Object,
    disabled: boolean,
    formInspector: FormInspector,
    hideEmpty: boolean,
    onChange: (name: string, value: mixed) => void,
    onFinish: (dataPath: string, schemaPath: string) => void,
    router: ?Router,
    schema: Schema,
    showAllErrors: boolean,
    toolbar?: Node,
|};

function isEmpty(value: mixed): boolean {
    return value === undefined || value === null || value === '';
}

/**
 * Draws a product_attributes form schema as one collapsible card per section and one label/field row
 * per attribute. The field itself is rendered by the form's Field, so every registered field type
 * works here unchanged. Values, errors and the modified state come from the given form inspector,
 * the one of the container's own store, keyed by field name.
 *
 * @experimental We can not yet give BC Promise for this new container in Sulu 3.1.
 */
@observer
class ProductAttributesRenderer extends React.Component<Props> {
    @computed get groups(): Array<Group> {
        const {formInspector, hideEmpty, schema} = this.props;

        return Object.keys(schema)
            .map((sectionKey) => {
                const section = schema[sectionKey];
                const items = section.items || {};

                const rows = Object.keys(items)
                    .filter((name) => name.startsWith(NAME_PREFIX))
                    .map((name) => ({name, schema: items[name]}))
                    .filter((row) => !hideEmpty || !isEmpty(formInspector.getValueByPath('/' + row.name)));

                return {rows, sectionKey, title: section.label || ''};
            })
            .filter((group) => group.rows.length > 0);
    }

    handleFieldChange = (name: string, fieldValue: mixed) => {
        this.props.onChange(name, fieldValue);
    };

    handleFieldFinish = (dataPath: string, schemaPath: string) => {
        this.props.onFinish(dataPath, schemaPath);
    };

    // Same rule as the form's Renderer: an error shows once the row was edited or the form asks for all.
    rowError(name: string, rowDataPath: string): Error | typeof undefined {
        const {formInspector, showAllErrors} = this.props;
        const {errors} = formInspector;

        if (!errors || !(name in errors)) {
            return undefined;
        }

        return showAllErrors || formInspector.isFieldModified(rowDataPath) ? errors[name] : undefined;
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
        const {data, formInspector, router} = this.props;
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
                        error={this.rowError(name, rowDataPath)}
                        formInspector={formInspector}
                        name={name}
                        onChange={this.handleFieldChange}
                        onFinish={this.handleFieldFinish}
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
