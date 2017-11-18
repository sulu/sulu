// @flow
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import React from 'react';
import ColumnList from '../../../components/ColumnList';
import type {DatagridAdapterProps} from '../types';

@observer
export default class ColumnListAdapter extends React.Component<DatagridAdapterProps> {
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
        onLoadChildren(id, columnIndex, hasChildren);
    };

    @action setSelectedItem = (id: string | number, columnIndex: number) => {
        this.selectedItems[columnIndex] = id;
    };

    render() {
        const {
            data,
            schema,
            selections,
            onItemClick,
            onAllSelectionChange,
            onLoadChildren,
        } = this.props;
        const schemaKeys = Object.keys(schema);

        const buttons = [
            {
                icon: 'pencil',
                onClick: (id) => {
                    alert('Clicked pencil button for item with id: ' + id);
                },
            },
        ];

        const toolbarItems = [
            {
                icon: 'plus',
                type: 'button',
                onClick: (index) => {
                    alert('Clicked plus button for item with index: ' + index);
                },
            },
            {
                icon: 'search',
                type: 'button',
                skin: 'secondary',
                onClick: (index) => {
                    alert('Clicked search button for column with index: ' + index);
                },
            },
            {
                icon: 'gear',
                type: 'dropdown',
                options: [
                    {
                        label: 'Option1 ',
                        onClick: (index) => {
                            alert('Clicked option1 for column with index: ' + index);
                        },
                    },
                    {
                        label: 'Option2 ',
                        onClick: (index) => {
                            alert('Clicked option2 for column with index: ' + index);
                        },
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
