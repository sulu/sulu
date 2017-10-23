// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Table from '../../../components/Table';
import type {DatagridAdapterProps} from '../types';

@observer
export default class TableAdapter extends React.Component<DatagridAdapterProps> {
    static defaultProps = {
        data: [],
    };

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
            <div>
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
                                {schemaKeys.map((schemaKey) => (
                                    <Table.Cell key={item.id + schemaKey}>{item[schemaKey]}</Table.Cell>
                                ))}
                            </Table.Row>
                        ))}
                    </Table.Body>
                </Table>
            </div>
        );
    }
}
