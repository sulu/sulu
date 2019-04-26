// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {arrayMove} from '../../components';
import MultiItemSelection from '../../components/MultiItemSelection';
import Item from './Item';
import type {TeaserItem, TeaserSelectionValue} from './types';

type Props = {|
    onChange: (TeaserSelectionValue) => void,
    value: TeaserSelectionValue,
|};

@observer
export default class TeaserSelection extends React.Component<Props> {
    @observable editIds: Array<number | string> = [];

    openItemEdit(id: number | string) {
        this.editIds.push(id);
    }

    closeItemEdit(id: number | string) {
        this.editIds.splice(this.editIds.findIndex((editId) => editId === id), 1);
    }

    @action handleCancel = (id: number | string) => {
        this.closeItemEdit(id);
    };

    @action handleEdit = (id: number | string) => {
        this.openItemEdit(id);
    };

    handleApply = (item: TeaserItem) => {
        const {onChange} = this.props;
        const value = {...this.props.value};

        const editIndex = value.items.findIndex((oldItem) => oldItem.id === item.id);
        value.items[editIndex] = item;

        onChange(value);

        this.closeItemEdit(item.id);
    };

    handleRemove = (id: number | string) => {
        const {onChange, value} = this.props;

        onChange({...value, items: value.items.filter((item) => item.id !== id)});
    };

    handleSorted = (oldItemIndex: number, newItemIndex: number) => {
        const {onChange, value} = this.props;

        onChange({...value, items: arrayMove(value.items, oldItemIndex, newItemIndex)});
    };

    render() {
        const {value} = this.props;

        return (
            <MultiItemSelection onItemsSorted={this.handleSorted}>
                {value.items.map((teaserItem, index) => (
                    <MultiItemSelection.Item
                        id={teaserItem.id}
                        index={index + 1}
                        key={teaserItem.id}
                        onEdit={this.editIds.includes(teaserItem.id) ? undefined : this.handleEdit}
                        onRemove={this.handleRemove}
                    >
                        <Item
                            description={teaserItem.description}
                            editing={this.editIds.includes(teaserItem.id)}
                            id={teaserItem.id}
                            onApply={this.handleApply}
                            onCancel={this.handleCancel}
                            title={teaserItem.title}
                            type={teaserItem.type}
                        />
                    </MultiItemSelection.Item>
                ))}
            </MultiItemSelection>
        );
    }
}
