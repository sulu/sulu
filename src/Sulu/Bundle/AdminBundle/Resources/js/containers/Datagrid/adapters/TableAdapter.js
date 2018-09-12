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
                <Table.Row id={item.id} key={item.id} selected={selections.includes(item.id)}>
                    {this.renderCells(item)}
                </Table.Row>
            );
        });
    }

    render() {
        const {
            limit,
            loading,
            onAllSelectionChange,
            onItemClick,
            onItemSelectionChange,
            onLimitChange,
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
                currentLimit={limit}
                currentPage={page}
                loading={loading}
                onLimitChange={onLimitChange}
                onPageChange={onPageChange}
                totalPages={pageCount}
            >
                <Table
                    buttons={buttons}
                    onAllSelectionChange={onAllSelectionChange}
                    onRowSelectionChange={onItemSelectionChange}
                    selectMode={onItemSelectionChange ? 'multiple' : undefined}
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
