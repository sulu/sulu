// @flow
import React from 'react';
import {comparer, computed, observable, reaction} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import ListStore from '../../containers/List/stores/ListStore';
import ListOverlay from '../ListOverlay';
import type {OverlayType} from '../ListOverlay';

const USER_SETTINGS_KEY = 'multi_list_overlay';

type Props = {|
    adapter: string,
    allowActivateForDisabledItems?: boolean,
    clearSelectionOnClose: boolean,
    confirmLoading?: boolean,
    disabledIds: Array<string | number>,
    excludedIds: Array<string | number>,
    itemDisabledCondition?: ?string,
    listKey: string,
    locale?: ?IObservableValue<string>,
    onClose: () => void,
    onConfirm: (selectedItems: Array<Object>) => void,
    open: boolean,
    options?: Object,
    overlayType: OverlayType,
    preSelectedItems: Array<Object>,
    preloadSelectedItems: boolean,
    reloadOnOpen?: boolean,
    resourceKey: string,
    title: string,
|};

@observer
class MultiListOverlay extends React.Component<Props> {
    static defaultProps = {
        clearSelectionOnClose: false,
        disabledIds: [],
        excludedIds: [],
        overlayType: 'overlay',
        preloadSelectedItems: true,
        preSelectedItems: [],
    };

    listStore: ListStore;
    page: IObservableValue<number> = observable.box(1);
    excludedIdsDisposer: () => void;
    changeOptionsDisposer: () => *;

    constructor(props: Props) {
        super(props);

        const excludedIds = computed(() => this.props.excludedIds.length ? this.props.excludedIds : undefined);
        this.excludedIdsDisposer = excludedIds.observe(() => this.listStore.clear());

        const {listKey, locale, options, preloadSelectedItems, preSelectedItems, resourceKey} = this.props;
        const observableOptions = {};
        observableOptions.page = this.page;
        observableOptions.excludedIds = excludedIds;

        if (locale) {
            observableOptions.locale = locale;
        }

        this.listStore = new ListStore(
            resourceKey,
            listKey,
            USER_SETTINGS_KEY,
            observableOptions,
            options,
            undefined,
            preloadSelectedItems ? preSelectedItems.map((preSelectedItem) => preSelectedItem.id) : undefined
        );

        this.changeOptionsDisposer = reaction(
            () => this.props.options,
            (options) => {
                // reset liststore to reload whole tree instead of children of current active item
                this.listStore.reset();
                // set selected items as initialSelectionIds to expand them in case of a tree
                this.listStore.initialSelectionIds = this.listStore.selectionIds;
                this.listStore.options = {...this.listStore.options, ...options};
            },
            {equals: comparer.structural}
        );
    }

    componentWillUnmount() {
        this.listStore.destroy();
        this.excludedIdsDisposer();
        this.changeOptionsDisposer();
    }

    handleConfirm = () => {
        this.props.onConfirm(this.listStore.selections);
    };

    render() {
        const {
            adapter,
            allowActivateForDisabledItems,
            clearSelectionOnClose,
            confirmLoading,
            disabledIds,
            itemDisabledCondition,
            onClose,
            open,
            overlayType,
            preSelectedItems,
            reloadOnOpen,
            title,
        } = this.props;

        return (
            <ListOverlay
                adapter={adapter}
                allowActivateForDisabledItems={allowActivateForDisabledItems}
                clearSelectionOnClose={clearSelectionOnClose}
                confirmLoading={confirmLoading}
                disabledIds={disabledIds}
                itemDisabledCondition={itemDisabledCondition}
                listStore={this.listStore}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                overlayType={overlayType}
                preSelectedItems={preSelectedItems}
                reloadOnOpen={reloadOnOpen}
                title={title}
            />
        );
    }
}

export default MultiListOverlay;
