// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {arrayMove} from '../../components';
import MultiItemSelection from '../../components/MultiItemSelection';
import TeaserStore from './stores/TeaserStore';
import Item from './Item';
import type {TeaserItem, TeaserSelectionValue} from './types';

type Props = {|
    locale: ?IObservableValue<string>,
    onChange: (TeaserSelectionValue) => void,
    value: TeaserSelectionValue,
|};

@observer
export default class TeaserSelection extends React.Component<Props> {
    static defaultProps = {
        value: {
            displayOption: undefined,
            items: [],
        },
    };

    @observable editIds: Array<number | string> = [];
    teaserStore: TeaserStore;

    constructor(props: Props) {
        super(props);

        action(() => {
            this.teaserStore = new TeaserStore();

            const {value} = this.props;
            value.items.forEach((item) => {
                this.teaserStore.add(item.type, item.id);
            });
        })();
    }

    @computed get teaserItems(): Array<TeaserItem> {
        return this.props.value.items.map((teaserItem) => ({
            ...this.teaserStore.findById(teaserItem.type, teaserItem.id),
            ...Object.keys(teaserItem).reduce((clearedTeaserItem, key) => {
                if (teaserItem[key] !== undefined) {
                    clearedTeaserItem[key] = teaserItem[key];
                }
                return clearedTeaserItem;
            }, {}),
        }));
    }

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

    @action handleApply = (item: TeaserItem) => {
        const {onChange} = this.props;
        const value = {...this.props.value};

        const editIndex = value.items.findIndex((oldItem) => oldItem.id === item.id);
        value.items[editIndex] = item;

        onChange(value);

        this.closeItemEdit(item.id);
    };

    handleRemove = (id: number | string) => {
        const {onChange, value} = this.props;

        // TODO also check for type
        onChange({...value, items: value.items.filter((item) => item.id !== id)});
    };

    handleSorted = (oldItemIndex: number, newItemIndex: number) => {
        const {onChange, value} = this.props;

        onChange({...value, items: arrayMove(value.items, oldItemIndex, newItemIndex)});
    };

    render() {
        const {locale} = this.props;

        return (
            <MultiItemSelection loading={this.teaserStore.loading} onItemsSorted={this.handleSorted}>
                {this.teaserItems.map((teaserItem, index) => (
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
                            locale={locale}
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
