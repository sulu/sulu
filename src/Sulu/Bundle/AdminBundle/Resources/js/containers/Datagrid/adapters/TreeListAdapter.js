// @flow
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import React, {Fragment} from 'react';
import Tree from '../../../components/Tree';
import TreeStructureStrategy from '../structureStrategies/TreeStructureStrategy';
import AbstractAdapter from './AbstractAdapter';
import FullLoadingStrategy from "../loadingStrategies/FullLoadingStrategy";

@observer
export default class TreeAdapter extends AbstractAdapter {
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
        return -1 !== this.expandedRows.indexOf(identifier);
    }

    flattenData(items: Array<Object>, dataList: Array<Object>, depth: number) {
        items.forEach((item) => {
            let expanded = this.isExpanded(item.data.id);
            item.expanded = expanded;

            if (!item.depth) {
                item.depth = depth;
            }

            dataList.push(item);

            if (expanded && item.children.length) {
                this.flattenData(item.children, dataList, ++depth);
            }
        });

        return dataList;
    }

    @action handleRowToggleChange(identifier: string | number, expanded: boolean) {
        if (expanded) {
            this.expandedRows.push(identifier);

            if (this.props.onItemActivation) {
                this.props.onItemActivation(identifier);
            }

            return;
        }

        this.expandedRows.splice(this.expandedRows.indexOf(identifier), 1);
    }

    renderCells(item: Object, schemaKeys: Array<string>) {
        return schemaKeys.map((schemaKey) => {
            let cellContent = item[schemaKey];

            // TODO: Remove this when a datafield mapping is built
            if (typeof item[schemaKey] === 'object') {
                cellContent = 'Object!';
            }

            return (
                <Tree.Cell key={item.id + schemaKey}>
                    {cellContent}
                </Tree.Cell>
            );
        });
    }

    renderHeaderCells() {
        const {
            schema,
        } = this.props;
        const schemaKeys = Object.keys(schema);

        return schemaKeys.map((schemaKey) => (
            <Tree.HeaderCell key={schemaKey}>
                {schemaKey}
            </Tree.HeaderCell>
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

            return <Tree.Row key={data.id}
                             id={data.id}
                             depth={item.depth}
                             hasChildren={data.hasChildren}
                             expanded={item.expanded}
                             selected={selections.includes(data.id)}>
                {this.renderCells(data, schemaKeys)}
            </Tree.Row>
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
            <Tree
                buttons={buttons}
                selectMode="multiple"
                onRowToggleChange={this.handleRowToggleChange.bind(this)}
                onRowSelectionChange={onItemSelectionChange}
                onAllSelectionChange={onAllSelectionChange}
            >
                <Tree.Header>
                    {this.renderHeaderCells()}
                </Tree.Header>

                <Tree.Body>
                    {this.renderRows(this.getDataList())}
                </Tree.Body>
            </Tree>
        );
    }
}
