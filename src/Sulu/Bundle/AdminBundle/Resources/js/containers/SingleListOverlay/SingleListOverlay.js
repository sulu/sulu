// @flow
import React from 'react';
import {autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import ListStore from '../../containers/List/stores/ListStore';
import ListOverlay from '../ListOverlay';
import type {OverlayType} from '../ListOverlay';

const USER_SETTINGS_KEY = 'single_list_overlay';

type Props = {|
    adapter: string,
    allowActivateForDisabledItems?: boolean,
    clearSelectionOnClose: boolean,
    confirmLoading?: boolean,
    listKey: string,
    disabledIds?: Array<string | number>,
    locale?: ?IObservableValue<string>,
    onClose: () => void,
    onConfirm: (selectedItem: Object) => void,
    open: boolean,
    options?: Object,
    overlayType: OverlayType,
    resourceKey: string,
    preSelectedItem?: ?Object,
    reloadOnOpen?: boolean,
    title: string,
|};

export default class SingleListOverlay extends React.Component<Props> {
    listStore: ListStore;
    page: IObservableValue<number> = observable.box(1);
    selectionDisposer: () => void;

    static defaultProps = {
        clearSelectionOnClose: false,
        overlayType: 'overlay',
    };

    constructor(props: Props) {
        super(props);

        const {listKey, locale, options, preSelectedItem, resourceKey} = this.props;
        const observableOptions = {};
        observableOptions.page = this.page;

        if (locale) {
            observableOptions.locale = locale;
        }

        const initialSelectionIds = [];
        if (preSelectedItem) {
            initialSelectionIds.push(preSelectedItem.id);
        }
        this.listStore = new ListStore(
            resourceKey,
            listKey,
            USER_SETTINGS_KEY,
            observableOptions,
            options,
            initialSelectionIds
        );

        this.selectionDisposer = autorun(() => {
            const {selections} = this.listStore;

            if (selections.length <= 1) {
                return;
            }

            const selection = selections[selections.length - 1];

            if (!selection) {
                return;
            }

            this.listStore.clearSelection();
            this.listStore.select(selection);
        });
    }

    componentWillUnmount() {
        this.listStore.destroy();
        this.selectionDisposer();
    }

    handleConfirm = () => {
        if (this.listStore.selections.length > 1) {
            throw new Error(
                'The SingleListOverlay can only handle single selection.'
                + 'This should not happen and is likely a bug.'
            );
        }

        this.props.onConfirm(this.listStore.selections[0]);
    };

    render() {
        const {
            adapter,
            allowActivateForDisabledItems,
            clearSelectionOnClose,
            confirmLoading,
            disabledIds,
            onClose,
            open,
            overlayType,
            preSelectedItem,
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
                listStore={this.listStore}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                overlayType={overlayType}
                preSelectedItems={preSelectedItem ? [preSelectedItem] : undefined}
                reloadOnOpen={reloadOnOpen}
                title={title}
            />
        );
    }
}
