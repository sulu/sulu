// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {Body, Cell, Header, HeaderCell, Row, Table} from '../../../components/Table';
import type {DataItem, Schema} from '../types';

type Props = {
    data: Array<DataItem>,
    selections: Array<number | string>,
    onRowEditClick?: (rowId: string | number) => void,
    onRowSelectionChange?: (rowId: string | number, selected?: boolean) => void,
    onAllSelectionChange?: (selected?: boolean) => void,
    schema: Schema,
};

@observer
export default class TableAdapter extends React.Component<Props> {
    render() {
        const {data, selections, schema, onRowEditClick, onRowSelectionChange, onAllSelectionChange} = this.props;
        const schemaKeys = Object.keys(schema);
        const buttons = [];

        if (onRowEditClick) {
            buttons.push({
                icon: 'pencil',
                onClick: (rowId) => onRowEditClick(rowId),
            });
        }

        return (
            <Table
                buttons={buttons}
                selectMode="multiple"
                onRowSelectionChange={onRowSelectionChange}
                onAllSelectionChange={onAllSelectionChange}
            >
                <Header>
                    {schemaKeys.map((schemaKey) => <HeaderCell key={schemaKey}>{schemaKey}</HeaderCell>)}
                </Header>
                <Body>
                    {data.map((item) => (
                        <Row key={item.id} id={item.id} selected={selections.includes(item.id)}>
                            {schemaKeys.map((schemaKey) => <Cell key={item.id + schemaKey}>{item[schemaKey]}</Cell>)}
                        </Row>
                    ))}
                </Body>
            </Table>
        );
    }
}
