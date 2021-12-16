// @flow
import React, {Fragment} from 'react';
import {action, comparer, observable, reaction, toJS} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import jexl from 'jexl';
import classNames from 'classnames';
import CroppedText from '../../components/CroppedText';
import MultiItemSelection from '../../components/MultiItemSelection';
import PublishIndicator from '../../components/PublishIndicator';
import MultiSelectionStore from '../../stores/MultiSelectionStore';
import MultiListOverlay from '../MultiListOverlay';
import multiSelectionStyles from './multiSelection.scss';
import type {IObservableValue} from 'mobx/lib/mobx';

type Props = {|
    adapter: string,
    allowDeselectForDisabledItems: boolean,
    disabled: boolean,
    disabledIds: Array<string | number>,
    displayProperties: Array<string>,
    icon: string,
    itemDisabledCondition?: ?string,
    label?: string,
    listKey: string,
    locale?: ?IObservableValue<string>,
    onChange: (selectedIds: Array<string | number>) => void,
    onItemClick?: (id: number | string, item: Object) => void,
    options: Object,
    overlayTitle: string,
    resourceKey: string,
    sortable: boolean,
    value: Array<string | number>,
|};

@observer
class MultiSelection extends React.Component<Props> {
    static defaultProps = {
        allowDeselectForDisabledItems: false,
        disabled: false,
        disabledIds: [],
        displayProperties: [],
        icon: 'su-plus',
        options: {},
        sortable: true,
        value: [],
    };

    selectionStore: MultiSelectionStore<string | number>;
    changeSelectionDisposer: () => *;
    changeOptionsDisposer: () => *;

    @observable overlayOpen: boolean = false;

    constructor(props: Props) {
        super(props);

        const {locale, options, resourceKey, value} = this.props;

        // TODO instead of creating the store here and passing the props required for this, we should pass a store prop
        this.selectionStore = new MultiSelectionStore(resourceKey, value, locale, 'ids', options);

        this.changeSelectionDisposer = reaction(
            () => (this.selectionStore.items.map((item) => item.id)),
            (loadedItemIds: Array<string | number>) => {
                const {onChange, value} = this.props;

                if (!equals(toJS(value), toJS(loadedItemIds))) {
                    onChange(loadedItemIds);
                }
            }
        );

        this.changeOptionsDisposer = reaction(
            () => this.props.options,
            (options) => {
                this.selectionStore.setRequestParameters(options);
                this.selectionStore.loadItems(this.props.value);
            },
            {equals: comparer.structural}
        );
    }

    componentDidUpdate() {
        const newIds = toJS(this.props.value);
        const loadedIds = toJS(this.selectionStore.items.map((item) => item.id));

        newIds.sort();
        loadedIds.sort();
        if (!equals(newIds, loadedIds)) {
            this.selectionStore.loadItems(newIds);
        }
    }

    componentWillUnmount() {
        this.changeSelectionDisposer();
        this.changeOptionsDisposer();
    }

    @action closeOverlay() {
        this.overlayOpen = false;
    }

    @action openOverlay() {
        this.overlayOpen = true;
    }

    @action handleOverlayOpen = () => {
        this.openOverlay();
    };

    @action handleOverlayClose = () => {
        this.closeOverlay();
    };

    handleOverlayConfirm = (selectedItems: Array<Object>) => {
        this.selectionStore.set(selectedItems);
        this.closeOverlay();
    };

    handleRemove = (id: number | string) => {
        this.selectionStore.removeById(id);
    };

    handleSorted = (oldItemIndex: number, newItemIndex: number) => {
        this.selectionStore.move(oldItemIndex, newItemIndex);
    };

    render() {
        const {
            adapter,
            allowDeselectForDisabledItems,
            listKey,
            disabled,
            disabledIds,
            displayProperties,
            icon,
            itemDisabledCondition,
            label,
            locale,
            onItemClick,
            options,
            overlayTitle,
            resourceKey,
            sortable,
        } = this.props;

        const {items, loading} = this.selectionStore;
        const columns = displayProperties.length;

        return (
            <Fragment>
                <MultiItemSelection
                    disabled={disabled}
                    label={label}
                    leftButton={{
                        icon,
                        onClick: this.handleOverlayOpen,
                    }}
                    loading={loading}
                    onItemClick={onItemClick}
                    onItemRemove={this.handleRemove}
                    onItemsSorted={this.handleSorted}
                    sortable={sortable}
                >
                    {items.map((item, index) => {
                        const itemDisabled = disabledIds.includes(item.id) ||
                            (!!itemDisabledCondition && jexl.evalSync(itemDisabledCondition, item));

                        const itemColumnClass = classNames(
                            multiSelectionStyles.itemColumn,
                            {
                                [multiSelectionStyles.disabled]: itemDisabled,
                            }
                        );

                        const {published = undefined, publishedState = undefined} = item;

                        return (
                            <MultiItemSelection.Item
                                allowRemoveWhileDisabled={allowDeselectForDisabledItems}
                                disabled={itemDisabled}
                                id={item.id}
                                index={index + 1}
                                key={item.id}
                                value={item}
                            >
                                <div className={multiSelectionStyles.itemContainer}>
                                    {(publishedState !== undefined || published !== undefined) &&
                                        !(publishedState && published) &&
                                            <div className={multiSelectionStyles.publishIndicator}>
                                                <PublishIndicator
                                                    draft={!publishedState}
                                                    published={!!published}
                                                />
                                            </div>
                                    }

                                    <div className={multiSelectionStyles.columnList}>
                                        {displayProperties.map((displayProperty) => (
                                            <span
                                                className={itemColumnClass}
                                                key={displayProperty}
                                                style={{width: 100 / columns + '%'}}
                                            >
                                                <CroppedText>{item[displayProperty]}</CroppedText>
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </MultiItemSelection.Item>
                        );
                    })}
                </MultiItemSelection>
                <MultiListOverlay
                    adapter={adapter}
                    disabledIds={disabledIds}
                    itemDisabledCondition={itemDisabledCondition}
                    listKey={listKey}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    options={options}
                    preSelectedItems={items}
                    resourceKey={resourceKey}
                    title={overlayTitle}
                />
            </Fragment>
        );
    }
}

export default MultiSelection;
