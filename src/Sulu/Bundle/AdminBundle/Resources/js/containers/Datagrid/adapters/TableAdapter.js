// @flow
import React from 'react';
import {Body, Cell, Header, HeaderCell, Row, Table} from '../../../components/Table';
import type {DataItem, Schema} from '../types';

type Props = {
    data: Array<DataItem>,
    onRowEditClick?: (rowId: string | number) => void,
    schema: Schema,
};

export default class TableAdapter extends React.Component<Props> {
    render() {
        const {data, schema, onRowEditClick} = this.props;
        const schemaKeys = Object.keys(schema);
        const buttons = [];

        if (onRowEditClick) {
            buttons.push({
                icon: 'pencil',
                onClick: (rowId) => onRowEditClick(rowId),
            });
        }

        return (
            <Table buttons={buttons} selectMode="multiple">
                <Header>
                    {schemaKeys.map((schemaKey) => <HeaderCell key={schemaKey}>{schemaKey}</HeaderCell>)}
                </Header>
                <Body>
                    {data.map((item) => (
                        <Row key={item.id} id={item.id}>
                            {schemaKeys.map((schemaKey) => <Cell key={item.id + schemaKey}>{item[schemaKey]}</Cell>)}
                        </Row>
                    ))}
                </Body>
            </Table>
        );
    }
}
