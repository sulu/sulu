// @flow
import React from 'react';
import Router from '../../../services/Router';
import Table from '../../../components/Table';
import fieldRegistry from '../../Form/registries/fieldRegistry';
import FormInspector from '../../Form/FormInspector';
import attributeGroupTableStyles from '../AttributeGroupTable/attributeGroupTable.scss';
import type {Entry, FieldColumn} from '../types';

type Props = {|
    column: FieldColumn,
    dataPath: string,
    disabled: boolean,
    entry: Entry,
    formInspector: FormInspector,
    onChange: (id: string, name: string, value: mixed) => void,
    router: ?Router,
    schemaPath: string,
|};

/**
 * One cell of the attribute table, rendered by the field type the column declares.
 *
 * @experimental We can not yet give BC Promise for this new container in Sulu 3.1.
 */
export default class AttributeFieldCell extends React.PureComponent<Props> {
    handleChange = (value: mixed) => {
        const {column, entry, onChange} = this.props;

        onChange(entry.id, column.name, value);
    };

    // The parent field finishes on every change, so the cell has nothing left to do.
    handleFinish = () => {};

    render() {
        const {column, dataPath, disabled, entry, formInspector, router, schemaPath} = this.props;
        const FieldType = fieldRegistry.get(column.type);

        return (
            <Table.Cell className={attributeGroupTableStyles.fieldCell}>
                <FieldType
                    data={entry}
                    dataPath={dataPath + '/' + entry.id + '/' + column.name}
                    defaultType={undefined}
                    disabled={disabled}
                    error={undefined}
                    fieldTypeOptions={fieldRegistry.getOptions(column.type)}
                    formInspector={formInspector}
                    label={column.name}
                    maxOccurs={undefined}
                    minOccurs={undefined}
                    onChange={this.handleChange}
                    onFinish={this.handleFinish}
                    onSuccess={undefined}
                    router={router}
                    schemaOptions={{}}
                    schemaPath={schemaPath + '/' + column.name}
                    showAllErrors={false}
                    types={undefined}
                    value={entry[column.name]}
                />
            </Table.Cell>
        );
    }
}
