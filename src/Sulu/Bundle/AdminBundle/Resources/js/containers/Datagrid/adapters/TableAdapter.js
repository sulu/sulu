// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Pagination from '../../../components/Pagination';
import Table from '../../../components/Table';
import PaginatedLoadingStrategy from '../loadingStrategies/PaginatedLoadingStrategy';
import FlatStructureStrategy from '../structureStrategies/FlatStructureStrategy';
import AbstractTableAdapter from './AbstractTableAdapter';

@observer
export default class TableAdapter extends AbstractTableAdapter {
    static LoadingStrategy = PaginatedLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-align-justify';

    renderRows() {
        const {data, selections} = this.props;

        return data.map((item) => {
            return (
                <Table.Row key={item.id} id={item.id} selected={selections.includes(item.id)}>
                    {this.renderCells(item)}
                </Table.Row>
            );
        });
    }

    render() {
        const {
            loading,
            onItemClick,
            onAllSelectionChange,
            onItemSelectionChange,
            onPageChange,
            page,
            pageCount,
        } = this.props;
        const buttons = [];

        if (onItemClick) {
            buttons.push({
                icon: 'su-pen',
                onClick: (rowId) => onItemClick(rowId),
            });
        }

        return (
            <Pagination
                total={pageCount}
                current={page}
                loading={loading}
                onChange={onPageChange}
            >
                <Table
                    buttons={buttons}
                    selectMode={onItemSelectionChange ? 'multiple' : undefined}
                    onRowSelectionChange={onItemSelectionChange}
                    onAllSelectionChange={onAllSelectionChange}
                >
                    <Table.Header>
                        {this.renderHeaderCells()}
                    </Table.Header>
                    <Table.Body>
                        {this.renderRows()}
                    </Table.Body>
                </Table>
            </Pagination>
        );
    }
}
