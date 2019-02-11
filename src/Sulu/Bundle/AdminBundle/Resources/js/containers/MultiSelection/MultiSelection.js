// @flow
import React, {Fragment} from 'react';
import {action, observable, reaction, toJS} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import {MultiItemSelection} from '../../components';
import MultiSelectionStore from '../../stores/MultiSelectionStore';
import MultiDatagridOverlay from '../MultiDatagridOverlay';
import multiSelectionStyles from './multiSelection.scss';

type Props = {|
    adapter: string,
    datagridKey: string,
    disabled: boolean,
    disabledIds: Array<string | number>,
    displayProperties: Array<string>,
    onChange: (selectedIds: Array<string | number>) => void,
    label?: string,
    locale?: ?IObservableValue<string>,
    icon: string,
    resourceKey: string,
    value: Array<string | number>,
    overlayTitle: string,
|};

@observer
export default class MultiSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        disabledIds: [],
        displayProperties: [],
        icon: 'su-plus',
        value: [],
    };

    selectionStore: MultiSelectionStore;
    changeDisposer: () => *;

    @observable overlayOpen: boolean = false;

    constructor(props: Props) {
        super(props);

        const {locale, resourceKey, value} = this.props;

        this.selectionStore = new MultiSelectionStore(resourceKey, value, locale);
        this.changeDisposer = reaction(
            () => (this.selectionStore.items.map((item) => item.id)),
            (loadedItemIds: Array<string | number>) => {
                const {onChange, value} = this.props;

                if (!equals(toJS(value), toJS(loadedItemIds))) {
                    onChange(loadedItemIds);
                }
            }
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
        this.changeDisposer();
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
            datagridKey,
            disabled,
            disabledIds,
            displayProperties,
            icon,
            label,
            locale,
            resourceKey,
            overlayTitle,
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
                    onItemRemove={this.handleRemove}
                    onItemsSorted={this.handleSorted}
                >
                    {items.map((item, index) => (
                        <MultiItemSelection.Item id={item.id} index={index + 1} key={item.id}>
                            <div>
                                {displayProperties.map((displayProperty) => (
                                    <span
                                        className={multiSelectionStyles.itemColumn}
                                        key={displayProperty}
                                        style={{width: 100 / columns + '%'}}
                                    >
                                        {item[displayProperty]}
                                    </span>
                                ))}
                            </div>
                        </MultiItemSelection.Item>
                    ))}
                </MultiItemSelection>
                <MultiDatagridOverlay
                    adapter={adapter}
                    datagridKey={datagridKey}
                    disabledIds={disabledIds}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    preSelectedItems={items}
                    resourceKey={resourceKey}
                    title={overlayTitle}
                />
            </Fragment>
        );
    }
}
