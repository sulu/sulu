// @flow
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import React, {Fragment} from 'react';
import Table from '../../../components/Table';
import TreeStructureStrategy from '../structureStrategies/TreeStructureStrategy';
import AbstractAdapter from './AbstractAdapter';
import FullLoadingStrategy from "../loadingStrategies/FullLoadingStrategy";

@observer
export default class TreeListAdapter extends AbstractAdapter {
    static LoadingStrategy = FullLoadingStrategy;

    static StructureStrategy = TreeStructureStrategy;

    static defaultProps = {
        data: [],
    };

    @observable expandedRows: Array<string | number> = [];

    getDataList() {
        let dataList = [],
            depth = 0;
        dataList = this.flattenData(this.props.data, dataList, depth);

        return dataList;
    }

    isExpanded(identifier: string | number) {
        return this.expandedRows.includes(identifier);
    }

    flattenData(items: Array<Object>, dataList: Array<Object>, depth: number) {
        items.forEach((item) => {
            let expanded = this.isExpanded(item.data.id);
            item.expanded = expanded;
            item.depth = depth;

            dataList.push(item);

            if (expanded && item.children.length) {
                this.flattenData(item.children, dataList, depth + 1);
            }
        });

        return dataList;
    }

    @action handleRowToggleChange(rowId: string | number, expanded: boolean) {
        if (expanded) {
            this.expandedRows.push(rowId);

            if (this.props.onItemActivation) {
                this.props.onItemActivation(rowId);
            }

            return;
        }

        this.expandedRows.splice(this.expandedRows.indexOf(rowId), 1);
    }

    renderCells(item: Object, schemaKeys: Array<string>) {
        return schemaKeys.map((schemaKey) => {
            let cellContent = item[schemaKey];

            // TODO: Remove this when a datafield mapping is built
            if (typeof item[schemaKey] === 'object') {
                cellContent = 'Object!';
            }

            return (
                <Table.Cell key={item.id + schemaKey}>
                    {cellContent}
                </Table.Cell>
            );
        });
    }

    renderHeaderCells() {
        const {
            schema,
        } = this.props;
        const schemaKeys = Object.keys(schema);

        return schemaKeys.map((schemaKey) => (
            <Table.HeaderCell key={schemaKey}>
                {schemaKey}
            </Table.HeaderCell>
        ));
    }

    renderRows(items: Array<Object>) {
        return items.map((item) => {
            const {
                schema,
                selections,
            } = this.props;
            const {data} = item;
            const schemaKeys = Object.keys(schema);
            let loading = false;

            if (this.props.active === data.id && this.props.loading) {
                loading = true;
            }

            return <Table.Row key={data.id}
                             id={data.id}
                             depth={item.depth}
                             isLoading={loading}
                             hasChildren={data.hasChildren}
                             expanded={item.expanded}
                             selected={selections.includes(data.id)}>
                {this.renderCells(data, schemaKeys)}
            </Table.Row>
        });
    }

    render() {
        const {
            onItemClick,
            onAddClick,
            onAllSelectionChange,
            onItemSelectionChange,
        } = this.props;
        const buttons = [];

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
                onRowToggleChange={this.handleRowToggleChange.bind(this)}
                onRowSelectionChange={onItemSelectionChange}
                onAllSelectionChange={onAllSelectionChange}
            >
                <Table.Header>
                    {this.renderHeaderCells()}
                </Table.Header>

                <Table.Body>
                    {this.renderRows(this.getDataList())}
                </Table.Body>
            </Table>
        );
    }
}
