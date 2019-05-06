// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {arrayMove, MultiItemSelection} from 'sulu-admin-bundle/components';
import {MultiListOverlay} from 'sulu-admin-bundle/containers';
import TeaserStore from './stores/TeaserStore';
import Item from './Item';
import teaserProviderRegistry from './registries/TeaserProviderRegistry';
import type {TeaserItem, TeaserSelectionValue} from './types';

type Props = {|
    disabled: boolean,
    locale: IObservableValue<string>,
    onChange: (TeaserSelectionValue) => void,
    value: TeaserSelectionValue,
|};

@observer
export default class TeaserSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        value: {
            displayOption: undefined,
            items: [],
        },
    };

    static Item = Item;

    @observable editIds: Array<number | string> = [];
    @observable openedOverlay: ?string = undefined;
    teaserStore: TeaserStore;

    constructor(props: Props) {
        super(props);

        action(() => {
            const {locale, value} = this.props;

            this.teaserStore = new TeaserStore(locale);

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
            edited: !!(teaserItem.description || teaserItem.mediaId || teaserItem.title),
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

        onChange({...value, items: value.items.filter((item) => item.id !== id)});
    };

    handleSorted = (oldItemIndex: number, newItemIndex: number) => {
        const {onChange, value} = this.props;

        onChange({...value, items: arrayMove(value.items, oldItemIndex, newItemIndex)});
    };

    @action handleClose = () => {
        this.openedOverlay = undefined;
    };

    @action handleConfirm = (items: Array<Object>) => {
        const {openedOverlay} = this;

        if (!openedOverlay) {
            throw new Error('There was no opened overlay defined! This should not happen and is likely a bug.');
        }

        const {onChange, value} = this.props;

        const oldItems = value.items
            .filter(
                (currentItem) => currentItem.type !== openedOverlay || items.find((item) => item.id === currentItem.id)
            );

        const newItems = items
            .filter((item) => !oldItems.find((oldItem) => oldItem.id === item.id && oldItem.type === openedOverlay))
            .map((item) => ({id: item.id, type: openedOverlay}));

        onChange({
            ...value,
            items: [...oldItems, ...newItems],
        });

        items.forEach((item) => {
            this.teaserStore.add(openedOverlay, item.id);
        });

        this.openedOverlay = undefined;
    };

    @action handleAddClick = (provider: ?string) => {
        this.openedOverlay = provider;
    };

    render() {
        const {disabled, locale, value} = this.props;

        const addButtonOptions = teaserProviderRegistry.keys.map((teaserProviderKey) => {
            const teaserProvider = teaserProviderRegistry.get(teaserProviderKey);

            return {
                label: teaserProvider.title,
                value: teaserProviderKey,
            };
        });

        return (
            <Fragment>
                <MultiItemSelection
                    disabled={disabled}
                    leftButton={{
                        icon: 'su-plus-circle',
                        onClick: this.handleAddClick,
                        options: addButtonOptions,
                    }}
                    loading={this.teaserStore.loading}
                    onItemsSorted={this.handleSorted}
                >
                    {this.teaserItems.map((teaserItem, index) => (
                        <MultiItemSelection.Item
                            id={teaserItem.id}
                            index={index + 1}
                            key={teaserItem.type + teaserItem.id}
                            onEdit={this.editIds.includes(teaserItem.id) ? undefined : this.handleEdit}
                            onRemove={this.handleRemove}
                        >
                            <Item
                                description={teaserItem.description}
                                edited={teaserItem.edited}
                                editing={this.editIds.includes(teaserItem.id)}
                                id={teaserItem.id}
                                locale={locale}
                                mediaId={teaserItem.mediaId}
                                onApply={this.handleApply}
                                onCancel={this.handleCancel}
                                title={teaserItem.title}
                                type={teaserItem.type}
                            />
                        </MultiItemSelection.Item>
                    ))}
                </MultiItemSelection>
                {teaserProviderRegistry.keys.map((teaserProviderKey) => (
                    <MultiListOverlay
                        adapter={teaserProviderRegistry.get(teaserProviderKey).listAdapter}
                        key={teaserProviderKey}
                        listKey={teaserProviderKey}
                        locale={locale}
                        onClose={this.handleClose}
                        onConfirm={this.handleConfirm}
                        open={this.openedOverlay === teaserProviderKey}
                        preloadSelectedItems={false}
                        preSelectedItems={value.items.filter((item) => item.type === teaserProviderKey)}
                        resourceKey={teaserProviderKey}
                        title={teaserProviderRegistry.get(teaserProviderKey).overlayTitle}
                    />
                ))}
            </Fragment>
        );
    }
}
