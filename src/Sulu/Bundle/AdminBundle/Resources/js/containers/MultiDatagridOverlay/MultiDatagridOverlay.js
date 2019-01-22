// @flow
import React from 'react';
import {observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import DatagridOverlay from '../DatagridOverlay';
import type {OverlayType} from '../DatagridOverlay';

const USER_SETTINGS_KEY = 'multi_datagrid_overlay';

type Props = {|
    adapter: string,
    allowActivateForDisabledItems?: boolean,
    clearSelectionOnClose: boolean,
    confirmLoading?: boolean,
    datagridKey: string,
    disabledIds?: Array<string | number>,
    locale?: ?IObservableValue<string>,
    onClose: () => void,
    onConfirm: (selectedItems: Array<Object>) => void,
    open: boolean,
    options?: Object,
    overlayType: OverlayType,
    resourceKey: string,
    preSelectedItems: Array<Object>,
    reloadOnOpen?: boolean,
    title: string,
|};

export default class MultiDatagridOverlay extends React.Component<Props> {
    datagridStore: DatagridStore;
    page: IObservableValue<number> = observable.box(1);

    static defaultProps = {
        clearSelectionOnClose: false,
        overlayType: 'overlay',
        preSelectedItems: [],
    };

    constructor(props: Props) {
        super(props);

        const {datagridKey, locale, options, preSelectedItems, resourceKey} = this.props;
        const observableOptions = {};
        observableOptions.page = this.page;

        if (locale) {
            observableOptions.locale = locale;
        }

        this.datagridStore = new DatagridStore(
            resourceKey,
            // TODO make optional
            datagridKey,
            USER_SETTINGS_KEY,
            observableOptions,
            options,
            preSelectedItems.map((preSelectedItem) => preSelectedItem.id)
        );
    }

    componentWillUnmount() {
        this.datagridStore.destroy();
    }

    handleConfirm = () => {
        this.props.onConfirm(this.datagridStore.selections);
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
            preSelectedItems,
            reloadOnOpen,
            title,
        } = this.props;

        return (
            <DatagridOverlay
                adapter={adapter}
                allowActivateForDisabledItems={allowActivateForDisabledItems}
                clearSelectionOnClose={clearSelectionOnClose}
                confirmLoading={confirmLoading}
                datagridStore={this.datagridStore}
                disabledIds={disabledIds}
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
