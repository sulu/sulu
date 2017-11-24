// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Table from '../../../components/Table';
import AbstractAdapter from './AbstractAdapter';

@observer
export default class TableAdapter extends AbstractAdapter {
    static getLoadingStrategy: () => string = () => { return 'pagination'; };
    static getStorageStrategy: () => string = () => { return 'flat'; };

    static defaultProps = {
        data: [],
    };

    renderCells(item: {[*]: *}, schemaKeys: Array<string>) {
        return schemaKeys.map((schemaKey) => {
            if (typeof item[schemaKey] === 'object') {
                return <Table.Cell key={item.id + schemaKey}>Object!</Table.Cell>;
            }

            return (
                <Table.Cell key={item.id + schemaKey}>{item[schemaKey]}</Table.Cell>
            );
        });
    }

    render() {
        const {
            data,
            schema,
            selections,
            onItemClick,
            onAllSelectionChange,
            onItemSelectionChange,
        } = this.props;
        const schemaKeys = Object.keys(schema);
        const buttons = [];

        if (onItemClick) {
            buttons.push({
                icon: 'pencil',
                onClick: (rowId) => onItemClick(rowId),
            });
        }

        return (
            <Table
                buttons={buttons}
                selectMode="multiple"
                onRowSelectionChange={onItemSelectionChange}
                onAllSelectionChange={onAllSelectionChange}
            >
                <Table.Header>
                    {schemaKeys.map((schemaKey) => (
                        <Table.HeaderCell key={schemaKey}>{schemaKey}</Table.HeaderCell>
                    ))}
                </Table.Header>
                <Table.Body>
                    {data.map((item) => (
                        <Table.Row key={item.id} id={item.id} selected={selections.includes(item.id)}>
                            {this.renderCells(item, schemaKeys)}
                        </Table.Row>
                    ))}
                </Table.Body>
            </Table>
        );
    }
}
