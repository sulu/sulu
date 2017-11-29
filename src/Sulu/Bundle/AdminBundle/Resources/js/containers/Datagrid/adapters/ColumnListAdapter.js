// @flow
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import React from 'react';
import ColumnList from '../../../components/ColumnList';
import AbstractAdapter from './AbstractAdapter';

@observer
export default class ColumnListAdapter extends AbstractAdapter {
    static getLoadingStrategy: () => string = () => { return 'pagination'; };
    static getStorageStrategy: () => string = () => { return 'tree'; };

    @observable selectedItems: Array<String> = [];

    renderItems = (items: Object, columnIndex: number) => {
        let selectedId = this.selectedItems[columnIndex];

        return items.map((item: Object) => {
            let selected = false;
            if (selectedId === item.id) {
                selected = true;
            }
            return (
                <ColumnList.Item selected={selected} key={item.id} id={item.id} hasChildren={item.hasSub}>
                    {item.title}
                </ColumnList.Item>
            );
        });
    };

    renderColumn = () => {
        const {data, depthLoading} = this.props;

        return data.map((items, index) => {
            if (depthLoading === index) {
                return (
                    <ColumnList.Column key={index} index={index} loading={true} />
                );
            }

            return (
                <ColumnList.Column key={index} index={index}>
                    {this.renderItems(items, index)}
                </ColumnList.Column>
            );
        });
    };

    handleItemClick = (id: string | number, columnIndex: number, hasChildren: boolean) => {
        const {onLoadChildren} = this.props;
        this.setSelectedItem(id, columnIndex);

        if (onLoadChildren) {
            onLoadChildren(id, columnIndex, hasChildren);
        }
    };

    @action setSelectedItem = (id: string | number, columnIndex: number) => {
        this.selectedItems[columnIndex] = id;
    };

    render() {
        const buttons = [
            {
                icon: 'pencil',
            },
        ];

        const toolbarItems = [
            {
                icon: 'plus',
                type: 'button',
            },
            {
                icon: 'search',
                type: 'button',
                skin: 'secondary',
            },
            {
                icon: 'gear',
                type: 'dropdown',
                options: [
                    {
                        label: 'Option1 ',
                    },
                    {
                        label: 'Option2 ',
                    },
                ],
            },
        ];

        return (
            <div style={{height: '60vh'}}>
                <ColumnList onItemClick={this.handleItemClick} buttons={buttons} toolbarItems={toolbarItems}>
                    {this.renderColumn()}
                </ColumnList>
            </div>
        );
    }
}
