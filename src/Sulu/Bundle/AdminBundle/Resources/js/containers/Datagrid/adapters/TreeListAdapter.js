// @flow
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import Table from '../../../components/Table';
import Loader from '../../../components/Loader';
import TreeStructureStrategy from '../structureStrategies/TreeStructureStrategy';
import FullLoadingStrategy from '../loadingStrategies/FullLoadingStrategy';
import AbstractTableAdapter from './AbstractTableAdapter';

@observer
export default class TreeListAdapter extends AbstractTableAdapter {
    static LoadingStrategy = FullLoadingStrategy;

    static StructureStrategy = TreeStructureStrategy;

    // TODO: When it's created use the correct icon here
    static icon = 'fa-sitemap';

    @observable expandedRows: Array<string | number> = [];

    @action handleRowCollapse = (rowId: string | number) => {
        this.expandedRows.splice(this.expandedRows.indexOf(rowId), 1);
    };

    @action handleRowExpand = (rowId: string | number) => {
        this.expandedRows.push(rowId);

        const {onItemActivation} = this.props;
        if (onItemActivation) {
            onItemActivation(rowId);
        }
    };

    @computed get data(): Array<Object> {
        let dataList = [],
            depth = 0;

        this.flattenData(this.props.data, dataList, depth);

        return dataList;
    }

    isExpanded(identifier: string | number) {
        return this.expandedRows.includes(identifier);
    }

    flattenData(items: Array<Object>, dataList: Array<Object>, depth: number) {
        items.forEach((item) => {
            item.expanded = this.isExpanded(item.data.id);
            item.depth = depth;

            dataList.push(item);

            if (item.expanded && item.children.length) {
                this.flattenData(item.children, dataList, depth + 1);
            }
        });
    }

    renderRows() {
        return this.data.map((item) => {
            const {
                selections,
            } = this.props;
            const {data} = item;

            return (
                <Table.Row
                    key={data.id}
                    id={data.id}
                    depth={item.depth}
                    isLoading={this.props.active === data.id && this.props.loading}
                    hasChildren={data.hasChildren}
                    expanded={item.expanded}
                    selected={selections.includes(data.id)}
                >
                    {this.renderCells(data)}
                </Table.Row>
            );
        });
    }

    render() {
        const {
            active,
            loading,
            onItemClick,
            onAddClick,
            onAllSelectionChange,
            onItemSelectionChange,
        } = this.props;
        const buttons = [];

        if (!active && loading) {
            return <Loader />;
        }

        if (onItemClick) {
            buttons.push({
                icon: 'su-pen',
                onClick: (rowId) => onItemClick(rowId),
            });
        }

        if (onAddClick) {
            buttons.push({
                icon: 'su-plus',
                onClick: (rowId) => onAddClick(rowId),
            });
        }

        return (
            <Table
                buttons={buttons}
                selectInFirstCell={true}
                selectMode="multiple"
                onRowCollapse={this.handleRowCollapse}
                onRowExpand={this.handleRowExpand}
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
        );
    }
}
