// @flow
import React, {Fragment} from 'react';
import {action, autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {MultiItemSelection} from '../../components';
import SelectionStore from '../../stores/SelectionStore';
import DatagridOverlay from './DatagridOverlay';
import selectionStyles from './selection.scss';

type Props = {|
    adapter: string,
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
export default class Selection extends React.Component<Props> {
    static defaultProps = {
        disabledIds: [],
        displayProperties: [],
        icon: 'su-plus',
        value: [],
    };

    selectionStore: SelectionStore;
    changeDisposer: () => void;
    changeAutorunInitialized: boolean = false;

    @observable overlayOpen: boolean = false;

    constructor(props: Props) {
        super(props);

        const {onChange, locale, resourceKey, value} = this.props;

        this.selectionStore = new SelectionStore(resourceKey, value, locale);
        this.changeDisposer = autorun(() => {
            const itemIds = this.selectionStore.items.map((item) => item.id);

            if (!this.changeAutorunInitialized) {
                this.changeAutorunInitialized = true;
                return;
            }

            onChange(itemIds);
        });
    }

    componentDidUpdate() {
        const {value: newValue} = this.props;

        if (newValue.every((id) => this.selectionStore.items.some((item) => item.id === id))) {
            return;
        }

        this.selectionStore.loadItems(newValue);
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
        const {adapter, disabledIds, displayProperties, icon, label, locale, resourceKey, overlayTitle} = this.props;
        const {items, loading} = this.selectionStore;
        const columns = displayProperties.length;

        return (
            <Fragment>
                <MultiItemSelection
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
                        <MultiItemSelection.Item key={item.id} id={item.id} index={index + 1}>
                            <div>
                                {displayProperties.map((displayProperty) => (
                                    <span
                                        className={selectionStyles.itemColumn}
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
                <DatagridOverlay
                    adapter={adapter}
                    disabledIds={disabledIds}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    resourceKey={resourceKey}
                    preSelectedItems={items}
                    title={overlayTitle}
                />
            </Fragment>
        );
    }
}
