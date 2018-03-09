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

    getData() {
        let data = [],
            depth = 0;
        data = this.flattenData(this.props.data, data, depth);

        return data;
    }

    isExpanded(identifier: string | number) {
        return -1 !== this.expandedRows.indexOf(identifier);
    }

    flattenData(items: Array<Object>, data: Array<Object>, depth: number) {
        items.forEach((item) => {
            if (!item.data.depth) {
                // TODO discuss should we get the depth from the API?
                item.data.depth = depth;
            }

            data.push(item.data);

            if (this.isExpanded(item.data.id) && item.children.length) {
                this.flattenData(item.children, data, ++depth);
            }
        });

        return data;
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

    renderRows(items) {
        return items.map((item) => {
            const {
                schema,
                selections,
            } = this.props;
            const schemaKeys = Object.keys(schema);

            return <Tree.Row key={item.id}
                             id={item.id}
                             depth={item.depth}
                             hasChildren={item.hasChildren}
                             expanded={this.isExpanded(item.id)}
                             selected={selections.includes(item.id)}>
                {this.renderCells(item, schemaKeys)}
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
                    {this.renderRows(this.getData())}
                </Tree.Body>
            </Tree>
        );
    }
}
