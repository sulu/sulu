// @flow
import {observer} from 'mobx-react';
import React from 'react';
import type {Element} from 'react';
import Pagination from '../../../components/Pagination';
import Table from '../../../components/Table';
import PaginatedLoadingStrategy from '../loadingStrategies/PaginatedLoadingStrategy';
import FlatStructureStrategy from '../structureStrategies/FlatStructureStrategy';
import AbstractTableAdapter from './AbstractTableAdapter';

@observer
class TableAdapter extends AbstractTableAdapter {
    static LoadingStrategy = PaginatedLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-align-justify';

    getButtons = (item: ?Object) => {
        const {
            actions,
            onItemClick,
        } = this.props;

        const {
            _permissions: {
                edit: editPermission = true,
                view: viewPermission = true,
            } = {},
        } = item || {};

        const buttons = [];

        if (onItemClick) {
            buttons.push({
                disabled: !viewPermission,
                icon: editPermission ? 'su-pen' : 'su-eye',
                onClick: onItemClick,
            });
        }

        if (actions) {
            buttons.push(...actions);
        }

        return buttons;
    };

    renderRows(): Array<Element<typeof Table.Row>> {
        const {data, disabledIds, selections} = this.props;

        return data.map((item) => {
            return (
                <Table.Row
                    buttons={this.getButtons(item)}
                    disabled={disabledIds.includes(item.id)}
                    id={item.id}
                    key={item.id}
                    selected={selections.includes(item.id)}
                >
                    {this.renderCells(item)}
                </Table.Row>
            );
        });
    }

    render() {
        const {
            data,
            limit,
            loading,
            onAllSelectionChange,
            onItemSelectionChange,
            onLimitChange,
            onPageChange,
            options: {
                skin = 'dark',
            },
            page,
            pageCount,
        } = this.props;

        const table = (
            <Table
                buttons={this.getButtons()}
                onAllSelectionChange={onAllSelectionChange}
                onRowSelectionChange={onItemSelectionChange}
                selectMode={onItemSelectionChange ? 'multiple' : undefined}
                skin={skin}
            >
                <Table.Header>
                    {this.renderHeaderCells()}
                </Table.Header>
                <Table.Body>
                    {this.renderRows()}
                </Table.Body>
            </Table>
        );

        if (page === 1 && data.length === 0) {
            return table;
        }

        if (pageCount === undefined) {
            return table;
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
                {table}
            </Pagination>
        );
    }
}

export default TableAdapter;
