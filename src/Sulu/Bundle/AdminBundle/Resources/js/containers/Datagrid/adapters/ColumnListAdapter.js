// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {TreeItem} from '../types';
import ColumnList from '../../../components/ColumnList';
import FullLoadingStrategy from '../loadingStrategies/FullLoadingStrategy';
import TreeStructureStrategy from '../structureStrategies/TreeStructureStrategy';
import AbstractAdapter from './AbstractAdapter';

@observer
export default class ColumnListAdapter extends AbstractAdapter {
    static LoadingStrategy = FullLoadingStrategy;

    static StructureStrategy = TreeStructureStrategy;

    static defaultProps = {
        data: [],
    };

    handleItemClick = (id: string | number) => {
        const {onItemActivation} = this.props;
        if (onItemActivation) {
            onItemActivation(id);
        }
    };

    prepareColumnData() {
        const columns = [];
        const tree = ((this.props.data: any): Array<TreeItem>);

        this.prepareColumnLevel(columns, tree);
        this.prepareColumnChildren(columns, tree);

        return columns;
    }

    prepareColumnLevel(columns: Array<Array<Object>>, tree: Array<TreeItem>) {
        for (let i = 0; i < tree.length; i++) {
            const item = tree[i];
            const {data, children} = item;

            if (data.id === this.props.active) {
                this.prepareColumnChildren(columns, children);
                return true;
            }

            const activeParent = this.prepareColumnLevel(columns, children);

            if (activeParent) {
                this.prepareColumnChildren(columns, children);
                return true;
            }
        }
    }

    prepareColumnChildren(columns: Array<Array<Object>>, children: Array<TreeItem>) {
        columns.unshift(children.map((child) => child.data));
    }

    render() {
        const columnData = this.prepareColumnData();

        return (
            <ColumnList onItemClick={this.handleItemClick} toolbarItems={[]}>
                {columnData.map((items, index) => (
                    <ColumnList.Column key={index}>
                        {items.map((item: Object) => (
                            // TODO: Don't access properties like "hasSub" or "title" directly
                            <ColumnList.Item id={item.id} key={item.id} hasChildren={item.hasSub}>
                                {item.title}
                            </ColumnList.Item>
                        ))}
                    </ColumnList.Column>
                ))}
            </ColumnList>
        );
    }
}
