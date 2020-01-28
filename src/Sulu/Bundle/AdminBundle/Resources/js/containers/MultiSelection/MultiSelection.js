// @flow
import React, {Fragment} from 'react';
import {action, observable, reaction, toJS} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import CroppedText from '../../components/CroppedText';
import MultiItemSelection from '../../components/MultiItemSelection';
import MultiSelectionStore from '../../stores/MultiSelectionStore';
import MultiListOverlay from '../MultiListOverlay';
import multiSelectionStyles from './multiSelection.scss';

type Props = {|
    adapter: string,
    disabled: boolean,
    disabledIds: Array<string | number>,
    displayProperties: Array<string>,
    icon: string,
    itemDisabledCondition?: ?string,
    label?: string,
    listKey: string,
    locale?: ?IObservableValue<string>,
    onChange: (selectedIds: Array<string | number>) => void,
    options: Object,
    overlayTitle: string,
    resourceKey: string,
    value: Array<string | number>,
|};

@observer
class MultiSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        disabledIds: [],
        displayProperties: [],
        icon: 'su-plus',
        options: {},
        value: [],
    };

    selectionStore: MultiSelectionStore<string | number>;
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
            listKey,
            disabled,
            disabledIds,
            displayProperties,
            icon,
            itemDisabledCondition,
            label,
            locale,
            resourceKey,
            options,
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
                                        <CroppedText>{item[displayProperty]}</CroppedText>
                                    </span>
                                ))}
                            </div>
                        </MultiItemSelection.Item>
                    ))}
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
